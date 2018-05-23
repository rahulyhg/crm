<?php


namespace Core\Entities;

use Core\Core\Exceptions\Error;

class EmailAddress extends \Core\Core\ORM\Entity
{

    protected function _setName($value)
    {
        if (empty($value)) {
            throw new Error("Not valid email address '{$value}'");
        }
        $this->valuesContainer['name'] = $value;
        $this->set('lower', strtolower($value));
    }
}
