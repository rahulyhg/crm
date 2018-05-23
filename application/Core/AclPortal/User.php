<?php


namespace Core\AclPortal;

use \Core\ORM\Entity;

class User extends \Core\Core\AclPortal\Base
{
    public function checkIsOwner(\Core\Entities\User $user, Entity $entity)
    {
        return $user->id === $entity->id;
    }
}

