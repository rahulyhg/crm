<?php


namespace Core\Core\ORM;

use \Core\Core\Utils\Util;

class EntityManager extends \Core\ORM\EntityManager
{
    protected $espoMetadata;

    private $hookManager;

    protected $user;

    protected $container;

    private $repositoryClassNameHash = array();

    private $entityClassNameHash = array();

    public function setContainer(\Core\Core\Container $container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getCoreMetadata()
    {
        return $this->espoMetadata;
    }

    public function setCoreMetadata($espoMetadata)
    {
        $this->espoMetadata = $espoMetadata;
    }

    public function setHookManager(\Core\Core\HookManager $hookManager)
    {
        $this->hookManager = $hookManager;
    }

    public function getHookManager()
    {
        return $this->hookManager;
    }

    public function normalizeRepositoryName($name)
    {
        if (empty($this->repositoryClassNameHash[$name])) {
            $className = '\\Core\\Custom\\Repositories\\' . Util::normilizeClassName($name);
            if (!class_exists($className)) {
                $className = $this->espoMetadata->getRepositoryPath($name);
            }
            $this->repositoryClassNameHash[$name] = $className;
        }
        return $this->repositoryClassNameHash[$name];
    }

    public function normalizeEntityName($name)
    {
        if (empty($this->entityClassNameHash[$name])) {
            $className = '\\Core\\Custom\\Entities\\' . Util::normilizeClassName($name);
            if (!class_exists($className)) {
                $className = $this->espoMetadata->getEntityPath($name);
            }
            $this->entityClassNameHash[$name] = $className;
        }
        return $this->entityClassNameHash[$name];
    }
}

