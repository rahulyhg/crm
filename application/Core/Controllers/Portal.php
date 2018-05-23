<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\Forbidden;

class Portal extends \Core\Core\Controllers\Record
{
    protected function checkControllerAccess()
    {
        $portalPermission = $this->getAcl()->get('portalPermission');
        if (!$portalPermission || $portalPermission === 'no') {
            throw new Forbidden();
        }
    }
}
