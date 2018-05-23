<?php


namespace Core\Core\Formula\Functions\ArrayGroup;

use \Core\Core\Exceptions\Error;

class IncludesType extends \Core\Core\Formula\Functions\Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value') || !is_array($item->value)) {
            throw new Error('Value for \'Array\\Includses\' item is not array.');
        }
        if (count($item->value) < 2) {
            throw new Error('Bad arguments passed to \'Array\\Includses\'.');
        }
        $list = $this->evaluate($item->value[0]);
        $needle = $this->evaluate($item->value[1]);

        if (!is_array($list)) {
            return false;
        }

        return in_array($needle, $list);
    }
}