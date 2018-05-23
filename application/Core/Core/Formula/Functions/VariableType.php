<?php


namespace Core\Core\Formula\Functions;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class VariableType extends Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            throw new Error();
        }

        $name = $item->value;

        if (is_null($name)) {
            throw new Error();
        }

        if (!property_exists($this->getVariables(), $name)) {
            throw new Error();
        }

        return $this->getVariables()->$name;
    }
}