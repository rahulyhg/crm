<?php


namespace Core\Core\ORM;

class RepositoryFactory extends \Core\ORM\RepositoryFactory
{
    protected $defaultRepositoryClassName = '\\Core\\Core\\ORM\\Repositories\\RDB';

    public function create($name)
    {
        $repository = parent::create($name);

        $dependencies = $repository->getDependencyList();
        foreach ($dependencies as $name) {
            $repository->inject($name, $this->entityManager->getContainer()->get($name));
        }
        return $repository;
    }
}

