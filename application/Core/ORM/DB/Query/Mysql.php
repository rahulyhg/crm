<?php


namespace Core\ORM\DB\Query;

use Core\ORM\Entity;
use Core\ORM\IEntity;
use Core\ORM\EntityFactory;
use PDO;

class Mysql extends Base
{
    public function limit($sql, $offset, $limit)
    {
        if (!is_null($offset) && !is_null($limit)) {
            $offset = intval($offset);
            $limit = intval($limit);
            $sql .= " LIMIT {$offset}, {$limit}";
        }
        return $sql;
    }
}
