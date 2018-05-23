<?php


namespace Core\Core\Formula\Functions\LogicalGroup;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class AndType extends \Core\Core\Formula\Functions\Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            return true;
        }

        if (is_null($item->value)) {
            return true;
        }

        if (!is_array($item->value)) {
            throw new Error('Value for \'And\' item is not array.');
        }

        $result = true;
        foreach ($item->value as $subItem) {
            $result = $result && $this->evaluate($subItem);
            if (!$result) break;
        }

        return $result;
    }
}