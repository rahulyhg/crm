<?php


namespace Core\Core\Formula\Functions\EntityGroup;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class AttributeFetchedType extends AttributeType
{
    protected function getAttributeValue($attribute)
    {
        return $this->attributeFetcher->fetch($this->getEntity(), $attribute, true);
    }
}