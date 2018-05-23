<?php


namespace Core\Core\Loaders;

abstract class Base implements \Core\Core\Interfaces\Loader
{
    private $container;

    public function __construct(\Core\Core\Container $container)
    {
        $this->container = $container;
    }

    protected function getContainer()
    {
        return $this->container;
    }
}