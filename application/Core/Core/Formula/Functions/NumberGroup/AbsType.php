<?php


namespace Core\Core\Formula\Functions\NumberGroup;

use \Core\Core\Exceptions\Error;

class AbsType extends \Core\Core\Formula\Functions\Base
{

    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            return true;
        }

        if (!is_array($item->value)) {
            throw new Error();
        }

        if (count($item->value) < 1) {
             throw new Error();
        }

        $value = $this->evaluate($item->value[0]);

        if (!is_numeric($value)) {
            return null;
        }

        return abs($value);
    }
}