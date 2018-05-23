<?php


namespace Core\Modules\Crm\AclPortal;

use \Core\Entities\User;
use \Core\ORM\Entity;

class Account extends \Core\Core\AclPortal\Base
{
    public function checkInAccount(User $user, Entity $entity)
    {
        $accountIdList = $user->getLinkMultipleIdList('accounts');
        if (count($accountIdList)) {
            if (in_array($entity->id, $accountIdList)) {
                return true;
            }
        }
        return false;
    }
}

