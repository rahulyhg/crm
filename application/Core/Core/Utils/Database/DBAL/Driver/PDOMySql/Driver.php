<?php
 

namespace Core\Core\Utils\Database\DBAL\Driver\PDOMySql;

class Driver extends \Doctrine\DBAL\Driver\PDOMySql\Driver 
{

    public function getDatabasePlatform()
    {
        return new \Core\Core\Utils\Database\DBAL\Platforms\MySqlPlatform();
    }
    
}