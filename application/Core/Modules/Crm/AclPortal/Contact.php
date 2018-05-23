<?php


namespace Core\Modules\Crm\AclPortal;

use \Core\Entities\User;
use \Core\ORM\Entity;

class Contact extends \Core\Core\AclPortal\Base
{
    public function checkIsOwnContact(User $user, Entity $entity)
    {
        $contactId = $user->get('contactId');
        if ($contactId) {
            if ($entity->id === $contactId) {
                return true;
            }
        }
        return false;
    }
}

