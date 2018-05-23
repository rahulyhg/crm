<?php


namespace Core\Core\Upgrades\Actions\Upgrade;

use Core\Core\Exceptions\Error;

class Delete extends \Core\Core\Upgrades\Actions\Base\Delete
{
    public function run($data)
    {
        throw new Error('The operation is not permitted.');
    }
}