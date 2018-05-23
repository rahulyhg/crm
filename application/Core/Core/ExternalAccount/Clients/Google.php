<?php


namespace Core\Core\ExternalAccount\Clients;

use \Core\Core\Exceptions\Error;

class Google extends OAuth2Abstract
{
    protected function getPingUrl()
    {
        return 'https://www.googleapis.com/calendar/v3/users/me/calendarList';
    }
}

