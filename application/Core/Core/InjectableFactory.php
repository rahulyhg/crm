<?php


namespace Core\Core;

use \Core\Core\Exceptions\Error;

class InjectableFactory
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function createByClassName($className)
    {
        if (class_exists($className)) {
            $service = new $className();
            if (!($service instanceof \Core\Core\Interfaces\Injectable)) {
                throw new Error("Class '$className' is not instance of Injectable interface");
            }
            $dependencyList = $service->getDependencyList();
            foreach ($dependencyList as $name) {
                $service->inject($name, $this->container->get($name));
            }
            return $service;
        }
        throw new Error("Class '$className' does not exist");
    }
}
