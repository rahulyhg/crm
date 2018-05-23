<?php


namespace Core\Modules\Crm\Repositories;

use Core\ORM\Entity;

class TargetList extends \Core\Core\ORM\Repositories\RDB
{
    protected $entityTypeLinkMap = array(
        'Lead' => 'leads',
        'Account' => 'accounts',
        'Contact' => 'contacts',
        'User' => 'users'
    );

    public function relateTarget(Entity $entity, Entity $target, $data = null)
    {
        if (empty($this->entityTypeLinkMap[$target->getEntityType()])) {
            return;
        }
        $relation = $this->entityTypeLinkMap[$target->getEntityType()];

        $this->relate($entity, $relation, $target, $data);
    }
}

