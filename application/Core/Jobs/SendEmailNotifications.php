<?php


namespace Core\Jobs;

use \Core\Core\Exceptions;

class SendEmailNotifications extends \Core\Core\Jobs\Base
{
    public function run()
    {
        $service = $this->getServiceFactory()->create('EmailNotification');
        $service->process();
    }
}

