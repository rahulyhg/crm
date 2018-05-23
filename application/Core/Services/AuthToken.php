<?php


namespace Core\Services;

use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\NotFound;

class AuthToken extends Record
{
    protected $internalAttributeList = ['hash', 'token'];

    protected $actionHistoryDisabled = true;
}

