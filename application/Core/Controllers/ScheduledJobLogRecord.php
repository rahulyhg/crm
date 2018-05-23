<?php
 

namespace Core\Controllers;

class ScheduledJobLogRecord extends \Core\Core\Controllers\Record
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }
}

