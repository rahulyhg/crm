<?php


namespace Core\Core\Formula\Functions\EntityGroup;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class AttributeType extends \Core\Core\Formula\Functions\AttributeType
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            throw new Error();
        }

        if (!is_array($item->value)) {
            throw new Error();
        }

        if (count($item->value) < 1) {
            throw new Error();
        }

        $attribute = $this->evaluate($item->value[0]);

        return $this->getAttributeValue($attribute);
    }
}