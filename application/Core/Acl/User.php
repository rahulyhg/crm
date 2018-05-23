<?php


namespace Core\Acl;

use \Core\ORM\Entity;

class User extends \Core\Core\Acl\Base
{
    public function checkIsOwner(\Core\Entities\User $user, Entity $entity)
    {
        return $user->id === $entity->id;
    }
}

