<?php


namespace Core\Services;

use \Core\ORM\Entity;

use \Core\Core\Exceptions\Forbidden;

class EmailFilter extends Record
{

    protected function beforeCreate(Entity $entity, array $data = array())
    {
        parent::beforeCreate($entity, $data);
        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }
    }
}

