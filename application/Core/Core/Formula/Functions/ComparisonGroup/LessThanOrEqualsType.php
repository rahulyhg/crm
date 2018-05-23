<?php


namespace Core\Core\Formula\Functions\ComparisonGroup;

use \Core\Core\Exceptions\Error;

class LessThanOrEqualsType extends Base
{
    protected function compare($left, $right)
    {
        return $left <= $right;
    }
}