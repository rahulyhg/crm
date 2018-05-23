<?php


namespace Core\Core\Formula\Functions\DatetimeGroup;

use \Core\Core\Exceptions\Error;

class AddMonthsType extends AddIntervalType
{
    protected $intervalTypeString = 'months';
}