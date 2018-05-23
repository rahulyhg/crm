<?php


namespace Core\Core\Formula\Functions\EntityGroup;

use \Core\ORM\Entity;

class IsAttributeNotChangedType extends IsAttributeChangedType
{
    protected function check($attribute)
    {
        return !parent::check($attribute);
    }
}