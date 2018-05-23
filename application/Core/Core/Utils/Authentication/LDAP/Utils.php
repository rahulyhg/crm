<?php


namespace Core\Core\Utils\Authentication\LDAP;
use \Core\Core\Utils\Config;

class Utils
{
    private $config;

    protected $options = null;

    /**
     * Association between LDAP and Core fields
     * @var array
     */
    protected $fieldMap = array(
        'host' => 'ldapHost',
        'port' => 'ldapPort',
        'useSsl' => 'ldapSecurity',
        'useStartTls' => 'ldapSecurity',
        'username' => 'ldapUsername',
        'password' => 'ldapPassword',
        'bindRequiresDn' => 'ldapBindRequiresDn',
        'baseDn' => 'ldapBaseDn',
        'accountCanonicalForm' => 'ldapAccountCanonicalForm',
        'accountDomainName' => 'ldapAccountDomainName',
        'accountDomainNameShort' => 'ldapAccountDomainNameShort',
        'accountFilterFormat' => 'ldapAccountFilterFormat',
        'optReferrals' => 'ldapOptReferrals',
        'tryUsernameSplit' => 'ldapTryUsernameSplit',
        'networkTimeout' => 'ldapNetworkTimeout',
        'createCoreUser' => 'ldapCreateCoreUser',
        'userNameAttribute' => 'ldapUserNameAttribute',
        'userTitleAttribute' => 'ldapUserTitleAttribute',
        'userFirstNameAttribute' => 'ldapUserFirstNameAttribute',
        'userLastNameAttribute' => 'ldapUserLastNameAttribute',
        'userEmailAddressAttribute' => 'ldapUserEmailAddressAttribute',
        'userPhoneNumberAttribute' => 'ldapUserPhoneNumberAttribute',
        'userLoginFilter' => 'ldapUserLoginFilter',
        'userTeamsIds' => 'ldapUserTeamsIds',
        'userDefaultTeamId' => 'ldapUserDefaultTeamId',
        'userObjectClass' => 'ldapUserObjectClass',
    );

    /**
     * Permitted Core Options
     *
     * @var array
     */
    protected $permittedCoreOptions = array(
        'createCoreUser',
        'userNameAttribute',
        'userObjectClass',
        'userTitleAttribute',
        'userFirstNameAttribute',
        'userLastNameAttribute',
        'userEmailAddressAttribute',
        'userPhoneNumberAttribute',
        'userLoginFilter',
        'userTeamsIds',
        'userDefaultTeamId',
    );

    /**
     * accountCanonicalForm Map between Core and Zend value
     *
     * @var array
     */
    protected $accountCanonicalFormMap = array(
        'Dn' => 1,
        'Username' => 2,
        'Backslash' => 3,
        'Principal' => 4,
    );


    public function __construct(Config $config = null)
    {
        if (isset($config)) {
            $this->config = $config;
        }
    }

    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * Get Options from espo config according to $this->fieldMap
     *
     * @return array
     */
    public function getOptions()
    {
        if (isset($this->options)) {
            return $this->options;
        }

        $options = array();
        foreach ($this->fieldMap as $ldapName => $espoName) {

            $option = $this->getConfig()->get($espoName);
            if (isset($option)) {
                $options[$ldapName] = $option;
            }
        }

        $this->options = $this->normalizeOptions($options);

        return $this->options;
    }

    /**
     * Normalize options to LDAP client format
     *
     * @param  array  $options
     *
     * @return array
     */
    public function normalizeOptions(array $options)
    {
        $options['useSsl'] = (bool) ($options['useSsl'] == 'SSL');
        $options['useStartTls'] = (bool) ($options['useStartTls'] == 'TLS');
        $options['accountCanonicalForm'] = $this->accountCanonicalFormMap[ $options['accountCanonicalForm'] ];

        return $options;
    }

    /**
     * Get an ldap option
     *
     * @param  string $name
     * @param  mixed $returns Return value
     * @return mixed
     */
    public function getOption($name, $returns = null)
    {
        if (isset($this->options)) {
            $this->getOptions();
        }

        if (isset($this->options[$name])) {
            return $this->options[$name];
        }

        return $returns;
    }

    /**
     * Get Zend options for using Zend\Ldap
     *
     * @return array
     */
    public function getLdapClientOptions()
    {
        $options = $this->getOptions();
        $zendOptions = array_diff_key($options, array_flip($this->permittedCoreOptions));

        return $zendOptions;
    }

}