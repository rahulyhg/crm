<?php


namespace Core\Core\Utils\Authentication;

use Core\Core\Exceptions\Error;
use Core\Core\Utils\Config;
use Core\Core\ORM\EntityManager;
use Core\Core\Utils\Auth;

class LDAP extends Base
{
    private $utils;

    private $ldapClient;

    /**
     * User field name  => option name (LDAP attribute)
     *
     * @var array
     */
    protected $ldapFieldMap = array(
        'userName' => 'userNameAttribute',
        'firstName' => 'userFirstNameAttribute',
        'lastName' => 'userLastNameAttribute',
        'title' => 'userTitleAttribute',
        'emailAddress' => 'userEmailAddressAttribute',
        'phoneNumber' => 'userPhoneNumberAttribute',
    );

    /**
     * User field name => option name
     *
     * @var array
     */
    protected $userFieldMap = array(
        'teamsIds' => 'userTeamsIds',
        'defaultTeamId' => 'userDefaultTeamId',
    );

    public function __construct(Config $config, EntityManager $entityManager, Auth $auth)
    {
        parent::__construct($config, $entityManager, $auth);

        $this->utils = new LDAP\Utils($config);
    }

    protected function getUtils()
    {
        return $this->utils;
    }

    protected function getLdapClient()
    {
        if (!isset($this->ldapClient)) {
            $options = $this->getUtils()->getLdapClientOptions();

            try {
                $this->ldapClient = new LDAP\Client($options);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('LDAP error: ' . $e->getMessage());
            }
        }

        return $this->ldapClient;
    }

    /**
     * LDAP login
     *
     * @param  string $username
     * @param  string $password
     * @param  \Core\Entities\AuthToken $authToken
     *
     * @return \Core\Entities\User | null
     */
    public function login($username, $password, \Core\Entities\AuthToken $authToken = null)
    {
        if ($authToken) {
            return $this->loginByToken($username, $authToken);
        }

        $ldapClient = $this->getLdapClient();

        //login LDAP system user (ldapUsername, ldapPassword)
        try {
            $ldapClient->bind();
        } catch (\Exception $e) {
            $options = $this->getUtils()->getLdapClientOptions();
            $GLOBALS['log']->error('LDAP: Could not connect to LDAP server ['.$options['host'].'], details: ' . $e->getMessage());

            $adminUser = $this->adminLogin($username, $password);
            if (!isset($adminUser)) {
                return null;
            }
            $GLOBALS['log']->info('LDAP: Administrator ['.$username.'] was logged in by Core method.');
        }

        if (!isset($adminUser)) {
            $userDn = $this->findLdapUserDnByUsername($username);
            $GLOBALS['log']->debug('Found DN for ['.$username.']: ['.$userDn.'].');
            if (!isset($userDn)) {
                $GLOBALS['log']->error('LDAP: Authentication failed for user ['.$username.'], details: user is not found.');
                return;
            }

            try {
                $ldapClient->bind($userDn, $password);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('LDAP: Authentication failed for user ['.$username.'], details: ' . $e->getMessage());
                return null;
            }
        }

        $user = $this->getEntityManager()->getRepository('User')->findOne(array(
            'whereClause' => array(
                'userName' => $username,
            ),
        ));

        $isCreateUser = $this->getUtils()->getOption('createCoreUser');
        if (!isset($user) && $isCreateUser) {
            $userData = $ldapClient->getEntry($userDn);
            $user = $this->createUser($userData);
        }

        return $user;
    }

    /**
     * Login by authorization token
     *
     * @param  string $username
     * @param  \Core\Entities\AuthToken $authToken
     *
     * @return \Core\Entities\User | null
     */
    protected function loginByToken($username, \Core\Entities\AuthToken $authToken = null)
    {
        if (!isset($authToken)) {
            return null;
        }

        $userId = $authToken->get('userId');
        $user = $this->getEntityManager()->getEntity('User', $userId);

        $tokenUsername = $user->get('userName');
        if ($username != $tokenUsername) {
            $GLOBALS['log']->alert('Unauthorized access attempt for user ['.$username.'] from IP ['.$_SERVER['REMOTE_ADDR'].']');
            return null;
        }

        $user = $this->getEntityManager()->getRepository('User')->findOne(array(
            'whereClause' => array(
                'userName' => $username,
            ),
        ));

        return $user;
    }

    /**
     * Login user with administrator rights
     *
     * @param  string $username
     * @param  string $password
     * @return \Core\Entities\User | null
     */
    protected function adminLogin($username, $password)
    {
        $hash = $this->getPasswordHash()->hash($password);

        $user = $this->getEntityManager()->getRepository('User')->findOne(array(
            'whereClause' => array(
                'userName' => $username,
                'password' => $hash,
                'isAdmin' => 1
            ),
        ));

        return $user;
    }

    /**
     * Create Core user with data gets from LDAP server
     *
     * @param  array $userData LDAP entity data
     *
     * @return \Core\Entities\User
     */
    protected function createUser(array $userData)
    {
        $GLOBALS['log']->info('Creating new user ...');
        $data = array();

        // show full array of the LDAP user
        $GLOBALS['log']->debug('LDAP: user data: ' .print_r($userData, true));

        //set values from ldap server
        $ldapFields = $this->loadFields('ldap');
        foreach ($ldapFields as $espo => $ldap) {
            $ldap = strtolower($ldap);
            if (isset($userData[$ldap][0])) {
                $GLOBALS['log']->debug('LDAP: Create a user wtih ['.$espo.'] = ['.$userData[$ldap][0].'].');
                $data[$espo] = $userData[$ldap][0];
            }
        }

        //set user fields
        $userFields = $this->loadFields('user');
        foreach ($userFields as $fieldName => $fieldValue) {
            $data[$fieldName] = $fieldValue;
        }

        $user = $this->getEntityManager()->getEntity('User');
        $user->set($data);

        $this->getEntityManager()->saveEntity($user);

        return $this->getEntityManager()->getEntity('User', $user->id);
    }

    /**
     * Find LDAP user DN by his username
     *
     * @param  string $username
     *
     * @return string | null
     */
    protected function findLdapUserDnByUsername($username)
    {
        $ldapClient = $this->getLdapClient();
        $options = $this->getUtils()->getOptions();

        $loginFilterString = '';
        if (!empty($options['userLoginFilter'])) {
            $loginFilterString = $this->convertToFilterFormat($options['userLoginFilter']);
        }

        $searchString = '(&(objectClass='.$options['userObjectClass'].')('.$options['userNameAttribute'].'='.$username.')'.$loginFilterString.')';
        $result = $ldapClient->search($searchString, null, LDAP\Client::SEARCH_SCOPE_SUB);
        $GLOBALS['log']->debug('LDAP: user search string: "' . $searchString . '"');

        foreach ($result as $item) {
            return $item["dn"];
        }
    }

    /**
     * Check and convert filter item into LDAP format
     *
     * @param  string $filter E.g. "memberof=CN=externalTesters,OU=groups,DC=espo,DC=local"
     *
     * @return string
     */
    protected function convertToFilterFormat($filter)
    {
        $filter = trim($filter);
        if (substr($filter, 0, 1) != '(') {
            $filter = '(' . $filter;
        }
        if (substr($filter, -1) != ')') {
            $filter = $filter . ')';
        }
        return $filter;
    }

    /**
     * Load fields for a user
     *
     * @param  string $type
     *
     * @return array
     */
    protected function loadFields($type)
    {
        $options = $this->getUtils()->getOptions();

        $typeMap = $type . 'FieldMap';

        $fields = array();
        foreach ($this->$typeMap as $fieldName => $fieldValue) {
            if (isset($options[$fieldValue])) {
                $fields[$fieldName] = $options[$fieldValue];
            }
        }

        return $fields;
    }
}