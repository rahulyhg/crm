<?php


namespace Core\Core\Utils\Database\DBAL\FieldTypes;

use Doctrine\DBAL\Types\StringType;

class VarcharType extends StringType
{
    const VARCHAR = 'varchar';

    public function getName()
    {
        return self::VARCHAR;
    }
}