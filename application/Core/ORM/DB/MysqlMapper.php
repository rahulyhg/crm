<?php


namespace Core\ORM\DB;
use Core\ORM\Entity;
use Core\ORM\Classes\EntityCollection;
use PDO;

/**
 * Abstraction for MySQL DB.
 * Mapping of Entity to DB.
 * Should be used internally only.
 */
class MysqlMapper extends Mapper
{
    protected function toDb($field)
    {
        return $this->query->toDb($field);
    }

}

