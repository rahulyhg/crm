<?php
 

namespace Core\Entities;

use Core\Core\Exceptions\Error;

class PhoneNumber extends \Core\Core\ORM\Entity
{
    protected function _setName($value)
    {
        if (empty($value)) {
            throw new Error("Phone number can't be empty");
        }
        $this->valuesContainer['name'] = $value;    
    }
}

