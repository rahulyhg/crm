<?php


namespace Core\EntryPoints;

use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;

class OauthCallback extends \Core\Core\EntryPoints\Base
{
    public static $authRequired = false;

    public function run()
    {
        echo "Samex CRM rocks !!!";
    }
}

