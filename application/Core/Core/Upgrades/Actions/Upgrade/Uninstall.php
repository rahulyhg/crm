<?php


namespace Core\Core\Upgrades\Actions\Upgrade;

use Core\Core\Exceptions\Error;

class Uninstall extends \Core\Core\Upgrades\Actions\Base\Uninstall
{
    public function run($data)
    {
        throw new Error('The operation is not permitted.');
    }
}