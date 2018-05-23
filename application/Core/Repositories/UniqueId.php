<?php


namespace Core\Repositories;

use Core\ORM\Entity;

class UniqueId extends \Core\Core\ORM\Repositories\RDB
{
    protected function getNewEntity()
    {
        $entity = parent::getNewEntity();
        $entity->set('name', uniqid());
        return $entity;
    }
}

