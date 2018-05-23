<?php


namespace Core\Core\Formula\Functions\ComparisonGroup;

use \Core\Core\Exceptions\Error;

class GreaterThanOrEqualsType extends Base
{
    protected function compare($left, $right)
    {
        return $left >= $right;
    }
}