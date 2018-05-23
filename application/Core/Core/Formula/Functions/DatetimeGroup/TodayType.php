<?php


namespace Core\Core\Formula\Functions\DatetimeGroup;

use \Core\Core\Exceptions\Error;

class TodayType extends \Core\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('dateTime');
    }

    public function process(\StdClass $item)
    {
        return $this->getInjection('dateTime')->getInternalTodayString();
    }
}