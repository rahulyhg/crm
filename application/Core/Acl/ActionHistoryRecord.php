<?php


namespace Core\Acl;

use \Core\Entities\User as EntityUser;
use \Core\ORM\Entity;

class ActionHistoryRecord extends \Core\Core\Acl\Base
{
    public function checkIsOwner(EntityUser $user, Entity $entity)
    {
        return $entity->get('userId') === $user->id;
    }
}

