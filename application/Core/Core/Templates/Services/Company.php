<?php


namespace Core\Core\Templates\Services;

use \Core\ORM\Entity;

class Company extends \Core\Services\Record
{
    protected function getDuplicateWhereClause(Entity $entity, $data = array())
    {
        return array(
            'name' => $entity->get('name')
        );
    }
}
