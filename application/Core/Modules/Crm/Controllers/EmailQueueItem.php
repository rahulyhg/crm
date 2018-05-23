<?php


namespace Core\Modules\Crm\Controllers;

class EmailQueueItem extends \Core\Core\Controllers\Record
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }
}
