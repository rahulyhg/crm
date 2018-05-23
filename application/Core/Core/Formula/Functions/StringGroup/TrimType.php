<?php


namespace Core\Core\Formula\Functions\StringGroup;

use \Core\Core\Exceptions\Error;

class TrimType extends \Core\Core\Formula\Functions\Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            return '';
        }

        if (!is_array($item->value)) {
            throw new Error();
        }

        if (count($item->value) < 1) {
            throw new Error();
        }

        $value = $this->evaluate($item->value[0]);

        if (!is_string($value)) {
            $value = strval($value);
        }

        return trim($value);
    }
}