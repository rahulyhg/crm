<?php


namespace Core\AclPortal;

use \Core\Entities\User as EntityUser;
use \Core\ORM\Entity;

class Notification extends \Core\Core\AclPortal\Base
{
    public function checkIsOwner(EntityUser $user, Entity $entity)
    {
        if ($user->id === $entity->get('userId')) {
            return true;
        }
        return false;
    }
}

