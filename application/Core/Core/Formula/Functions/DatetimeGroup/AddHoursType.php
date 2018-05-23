<?php


namespace Core\Core\Formula\Functions\DatetimeGroup;

use \Core\Core\Exceptions\Error;

class AddHoursType extends AddIntervalType
{
    protected $intervalTypeString = 'hours';

    protected $timeOnly = true;
}