<?php


namespace Core\Core\Formula\Functions\NumericGroup;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class MultiplicationType extends \Core\Core\Formula\Functions\Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            return '';
        }

        if (!is_array($item->value)) {
            throw new Error('Value for \'Multiplication\' item is not array.');
        }

        $result = 1;
        foreach ($item->value as $subItem) {
            $part = $this->evaluate($subItem);

            if (!is_float($part) && !is_int($part)) {
                $part = intval($part);
            }

            $result *= $part;
        }

        return $result;
    }
}