<?php


namespace Core\Core\Formula\Functions\LogicalGroup;

use \Core\Core\Exceptions\Error;

class NotType extends \Core\Core\Formula\Functions\Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            return true;
        }

        if (is_null($item->value)) {
            return true;
        }

        return  !$this->evaluate($item->value);

    }
}