<?php


namespace Core\Core\Services;

use \Core\Core\Interfaces\Injectable;

abstract class Base implements Injectable
{
    protected $dependencies = array(
        'config',
        'entityManager',
        'user',
    );

    protected $injections = array();

    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
    }

    protected function getInjection($name)
    {
        return $this->injections[$name];
    }

    protected function addDependency($name)
    {
        $this->dependencies[] = $name;
    }

    protected function addDependencyList(array $list)
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }

    public function getDependencyList()
    {
        return $this->dependencies;
    }

    protected function getEntityManager()
    {
        return $this->getInjection('entityManager');
    }

    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    protected function getUser()
    {
        return $this->getInjection('user');
    }
}

