<?php


namespace Core\Modules\Crm\Services;

use \Core\ORM\Entity;

class Account extends \Core\Services\Record
{
    protected $linkSelectParams = array(
        'contacts' => array(
            'additionalColumns' => array(
                'role' => 'accountRole',
                'isInactive' => 'accountIsInactive'
            )
        )
    );

    protected function getDuplicateWhereClause(Entity $entity, $data = array())
    {
        return array(
            'name' => $entity->get('name')
        );
    }
}

