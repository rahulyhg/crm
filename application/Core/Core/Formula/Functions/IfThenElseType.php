<?php


namespace Core\Core\Formula\Functions;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class IfThenElseType extends Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            return true;
        }

        if (!is_array($item->value)) {
            throw new Error('Value for \'IfThenElse\' item is not array.');
        }

        if (count($item->value) < 2) {
             throw new Error('Bad value for \'IfThenElse\' item.');
        }

        if ($this->evaluate($item->value[0])) {
            return $this->evaluate($item->value[1]);
        } else {
            if (count($item->value) > 2) {
                return $this->evaluate($item->value[2]);
            }
        }
    }
}