<?php


namespace Core\Core\Formula\Functions\DateTimeGroup;

use \Core\Core\Exceptions\Error;

class HourType extends \Core\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('dateTime');
    }

    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            return true;
        }

        if (!is_array($item->value)) {
            throw new Error();
        }

        if (count($item->value) < 1) {
             throw new Error();
        }

        $value = $this->evaluate($item->value[0]);

        if (empty($value)) return -1;

        if (strlen($value) > 11) {
            $resultString = $this->getInjection('dateTime')->convertSystemDateTime($value, null, 'H');
        } else {
            return 0;
        }

        return intval($resultString);
    }
}