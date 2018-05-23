<?php


namespace Core\Core\Formula\Functions\ComparisonGroup;

use \Core\Core\Exceptions\Error;

class NotEqualsType extends EqualsType
{
    protected function compare($left, $right)
    {
        return !parent::compare($left, $right);
    }
}