<?php


namespace Core\Core\ORM;

use \Core\Core\Interfaces\Injectable;

use \Core\ORM\EntityFactory;

abstract class Repository extends \Core\ORM\Repository implements Injectable
{
    protected $dependencies = array();

    protected $injections = array();

    protected function init()
    {
    }

    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    protected function getInjection($name)
    {
        return $this->injections[$name];
    }

    public function getDependencyList()
    {
        return $this->dependencies;
    }

    protected function addDependencyList(array $list)
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }

    protected function addDependency($name)
    {
        $this->dependencies[] = $name;
    }

    public function __construct($entityType, EntityManager $entityManager, EntityFactory $entityFactory)
    {
        parent::__construct($entityType, $entityManager, $entityFactory);
        $this->init();
    }
}

