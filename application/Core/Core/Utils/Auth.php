<?php


namespace Core\Core\Utils;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;

use \Core\Entities\Portal;

class Auth
{
    protected $container;

    protected $authentication;

    protected $allowAnyAccess;

    const ACCESS_CRM_ONLY = 0;

    const ACCESS_PORTAL_ONLY = 1;

    const ACCESS_ANY = 3;

    private $portal;

    public function __construct(\Core\Core\Container $container, $allowAnyAccess = false)
    {
        $this->container = $container;

        $this->allowAnyAccess = $allowAnyAccess;

        $authenticationMethod = $this->getConfig()->get('authenticationMethod', 'Core');
        $authenticationClassName = "\\Core\\Core\\Utils\\Authentication\\" . $authenticationMethod;
        $this->authentication = new $authenticationClassName($this->getConfig(), $this->getEntityManager(), $this);

        $this->request = $container->get('slim')->request();
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function setPortal(Portal $portal)
    {
        $this->portal = $portal;
    }

    protected function isPortal()
    {
        if ($this->portal) {
            return true;
        }
        return !!$this->getContainer()->get('portal');
    }

    protected function getPortal()
    {
        if ($this->portal) {
            return $this->portal;
        }
        return $this->getContainer()->get('portal');
    }

    protected function getConfig()
    {
        return $this->getContainer()->get('config');
    }

    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    public function useNoAuth()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $user = $entityManager->getRepository('User')->get('system');
        if (!$user) {
            throw new Error("System user is not found");
        }

        $user->set('isAdmin', true);
        $user->set('ipAddress', $_SERVER['REMOTE_ADDR']);

        $entityManager->setUser($user);
        $this->getContainer()->setUser($user);
    }

    public function login($username, $password)
    {
        $authToken = $this->getEntityManager()->getRepository('AuthToken')->where(array(
            'token' => $password,
            'isActive' => true
        ))->findOne();

        if ($authToken) {
            if (!$this->allowAnyAccess) {
                if ($this->isPortal() && $authToken->get('portalId') !== $this->getPortal()->id) {
                    $GLOBALS['log']->debug("AUTH: Trying to login to portal with a token not related to portal.");
                    return false;
                }
                if (!$this->isPortal() && $authToken->get('portalId')) {
                    $GLOBALS['log']->debug("AUTH: Trying to login to crm with a token related to portal.");
                    return false;
                }
            }
            if ($this->allowAnyAccess) {
                if ($authToken->get('portalId') && !$this->isPortal()) {
                    $portal = $this->getEntityManager()->getEntity('Portal', $authToken->get('portalId'));
                    if ($portal) {
                        $this->setPortal($portal);
                    }
                }
            }
        }

        $user = $this->authentication->login($username, $password, $authToken);

        if ($user) {
            if (!$user->isActive()) {
                $GLOBALS['log']->debug("AUTH: Trying to login as user '".$user->get('userName')."' which is not active.");
                return false;
            }

            if (!$user->isAdmin() && !$this->isPortal() && $user->get('isPortalUser')) {
                $GLOBALS['log']->debug("AUTH: Trying to login to crm as a portal user '".$user->get('userName')."'.");
                return false;
            }

            if (!$user->isAdmin() && $this->isPortal() && !$user->get('isPortalUser')) {
                $GLOBALS['log']->debug("AUTH: Trying to login to portal as user '".$user->get('userName')."' which is not portal user.");
                return false;
            }

            if ($this->isPortal()) {
                if (!$user->isAdmin() && !$this->getEntityManager()->getRepository('Portal')->isRelated($this->getPortal(), 'users', $user)) {
                    $GLOBALS['log']->debug("AUTH: Trying to login to portal as user '".$user->get('userName')."' which is portal user but does not belongs to portal.");
                    return false;
                }
                $user->set('portalId', $this->getPortal()->id);
            } else {
                $user->loadLinkMultipleField('teams');
            }

            $user->set('ipAddress', $_SERVER['REMOTE_ADDR']);

            $this->getEntityManager()->setUser($user);
            $this->getContainer()->setUser($user);

            if ($this->request->headers->get('HTTP_ESPO_AUTHORIZATION')) {
	            if (!$authToken) {
	                $authToken = $this->getEntityManager()->getEntity('AuthToken');
	                $token = $this->createToken($user);
	                $authToken->set('token', $token);
	                $authToken->set('hash', $user->get('password'));
	                $authToken->set('ipAddress', $_SERVER['REMOTE_ADDR']);
	                $authToken->set('userId', $user->id);
                    if ($this->isPortal()) {
                        $authToken->set('portalId', $this->getPortal()->id);
                    }
	            }
            	$authToken->set('lastAccess', date('Y-m-d H:i:s'));

            	$this->getEntityManager()->saveEntity($authToken);
            	$user->set('token', $authToken->get('token'));
                $user->set('authTokenId', $authToken->id);
            }

            return true;
        }
    }

    protected function createToken($user)
    {
        return md5(uniqid($user->get('id')));
    }

    public function destroyAuthToken($token)
    {
        $authToken = $this->getEntityManager()->getRepository('AuthToken')->where(array('token' => $token))->findOne();
        if ($authToken) {
            $authToken->set('isActive', false);
            $this->getEntityManager()->saveEntity($authToken);
            return true;
        }
    }
}

