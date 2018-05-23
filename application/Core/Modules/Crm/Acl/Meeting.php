<?php


namespace Core\Modules\Crm\Acl;

use \Core\Entities\User;
use \Core\ORM\Entity;

class Meeting extends \Core\Core\Acl\Base
{
    public function checkEntityRead(User $user, Entity $entity, $data)
    {
        if ($this->checkEntity($user, $entity, $data, 'read')) {
            return true;
        }

        if (is_object($data)) {
            if ($data->read === 'own' || $data->read === 'team') {
                if ($entity->hasLinkMultipleId('users', $user->id)) {
                    return true;
                }
            }
        }

        return false;
    }
}

