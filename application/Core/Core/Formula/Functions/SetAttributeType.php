<?php


namespace Core\Core\Formula\Functions;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class SetAttributeType extends Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            throw new Error();
        }

        if (!is_array($item->value)) {
            throw new Error();
        }

        if (count($item->value) < 2) {
            throw new Error();
        }

        $name = $this->evaluate($item->value[0]);

        if (!is_string($name)) {
            throw new Error();
        }

        $value = $this->evaluate($item->value[1]);

        $this->getEntity()->set($name, $value);

        return $value;
    }
}