<?php
/*********************************************************************************
 * The contents of this file are subject to the Samex CRM Advanced
 * Agreement ("License") which can be viewed at
 * http://www.espocrm.com/advanced-pack-agreement.
 * By installing or using this file, You have unconditionally agreed to the
 * terms and conditions of the License, and You may not use this file except in
 * compliance with the License.  Under the terms of the license, You shall not,
 * sublicense, resell, rent, lease, distribute, or otherwise  transfer rights
 * or usage to the software.
 * 
 * License ID: bcac485dee9efd0f36cf6842ad5b69b4
 ***********************************************************************************/

namespace Core\Modules\Advanced\Core\Google\Actions;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\NotFound;

abstract class Base
{	
    protected $baseUrl = 'https://www.googleapis.com/calendar/v3/';
    protected $userId;

    protected $configPath = 'data/google/config.json';

    protected $entityManager;
    protected $acl;
    protected $container;
    protected $metadata;

    protected $clientMap = array();

    public function __construct($container, $entityManager, $metadata, $config)
    {
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->config = $config;
        $this->container = $container;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getAcl()
    {
        return $this->acl;
    }

    protected function setAcl()
    {
        $user = $this->getEntityManager()->getEntity('User', $this->getUserId());
        if (!$user) {
            throw new Error("No User with id: " . $this->getUserId());
        }

        $aclManagerClassName = '\\Core\\Core\\AclManager';
        if (class_exists($aclManagerClassName)) {
            $aclManager = new $aclManagerClassName($this->getContainer());
            $this->acl = new \Core\Core\Acl($aclManager, $user);
        } else {
            $this->acl = new \Core\Core\Acl($user, $this->getConfig(), null, $this->getMetadata());
        }
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
        $this->setAcl();
    }

    public function getUserId()
    {
        return $this->userId;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getClient()
    {
        $factory = new \Core\Core\ExternalAccount\ClientManager($this->getEntityManager(), $this->getMetadata(), $this->getConfig());

        return $factory->create('Google', $this->getUserId());
    }

}
