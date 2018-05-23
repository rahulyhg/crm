<?php


namespace Core\Core;

use \Core\Core\Exceptions\Error;

use \Core\Core\Utils\Util;

class SelectManagerFactory
{
    private $entityManager;

    private $user;

    private $acl;

    private $metadata;

    public function __construct($entityManager, \Core\Entities\User $user, Acl $acl, AclManager $aclManager, Utils\Metadata $metadata, Utils\Config $config)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->acl = $acl;
        $this->aclManager = $aclManager;
        $this->metadata = $metadata;
        $this->config = $config;
    }

    public function create($entityType)
    {
        $normalizedName = Util::normilizeClassName($entityType);

        $className = '\\Core\\Custom\\SelectManagers\\' . $normalizedName;
        if (!class_exists($className)) {
            $moduleName = $this->metadata->getScopeModuleName($entityType);
            if ($moduleName) {
                $className = '\\Core\\Modules\\' . $moduleName . '\\SelectManagers\\' . $normalizedName;
            } else {
                $className = '\\Core\\SelectManagers\\' . $normalizedName;
            }
            if (!class_exists($className)) {
                $className = '\\Core\\Core\\SelectManagers\\Base';
            }
        }

        $selectManager = new $className($this->entityManager, $this->user, $this->acl, $this->aclManager, $this->metadata, $this->config);
        $selectManager->setEntityType($entityType);

        return $selectManager;
    }
}

