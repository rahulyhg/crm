<?php


namespace Core\Core\Formula\Functions\DatetimeGroup;

use \Core\Core\Exceptions\Error;

class AddMinutesType extends AddIntervalType
{
    protected $intervalTypeString = 'minutes';

    protected $timeOnly = true;
}