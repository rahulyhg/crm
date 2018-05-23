<?php


namespace Core\Core\Utils\Database;

use Core\Core\Utils\Util,
    Core\ORM\Entity;

class Converter
{
    private $metadata;

    private $fileManager;

    private $schemaConverter;

    private $schemaFromMetadata = null;

    public function __construct(\Core\Core\Utils\Metadata $metadata, \Core\Core\Utils\File\Manager $fileManager)
    {
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->ormConverter = new Orm\Converter($this->metadata, $this->fileManager);
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getOrmConverter()
    {
        return $this->ormConverter;
    }

    public function process()
    {
        $data = $this->getOrmConverter()->process();

        return $data;
    }
}