<?php


namespace Core\Repositories;

use Core\ORM\Entity;

class AuthToken extends \Core\Core\ORM\Repositories\RDB
{
    protected $hooksDisabled = true;
}
