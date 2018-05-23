<?php
 

namespace Core\Core\Utils\Database\DBAL\Driver\Mysqli;

class Driver extends \Doctrine\DBAL\Driver\Mysqli\Driver 
{

    public function getDatabasePlatform()
    {
        return new \Core\Core\Utils\Database\DBAL\Platforms\MySqlPlatform();
    }
    
}