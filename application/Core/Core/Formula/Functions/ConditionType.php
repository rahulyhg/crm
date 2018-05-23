<?php


namespace Core\Core\Formula\Functions;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class ConditionType extends Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            return true;
        }

        return $this->evaluate($item->value);
    }
}