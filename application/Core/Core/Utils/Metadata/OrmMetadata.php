<?php


namespace Core\Core\Utils\Metadata;

use Core\Core\Utils\Util;

class OrmMetadata
{
    protected $data = array();

    protected $cacheFile = 'data/cache/application/ormMetadata.php';

    protected $metadata;

    protected $fileManager;

    protected $useCache;

    public function __construct($metadata, $fileManager, $useCache = false)
    {
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->useCache = $useCache;
    }

    protected function getConverter()
    {
        if (!isset($this->converter)) {
            $this->converter = new \Core\Core\Utils\Database\Converter($this->metadata, $this->fileManager);
        }

        return $this->converter;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    public function clearData()
    {
        $this->ormData = null;
    }

    public function getData($reload = false)
    {
        if (!empty($this->ormData) && !$reload) {
            return $data;
        }

        if (!file_exists($this->cacheFile) || !$this->useCache || $reload) {
            $this->data = $this->getConverter()->process();

            if ($this->useCache) {
                $result = $this->getFileManager()->putPhpContents($this->cacheFile, $this->data);
                if ($result == false) {
                    throw new \Core\Core\Exceptions\Error('OrmMetadata::getData() - Cannot save ormMetadata to cache file');
                }
            }
        }

        if (empty($this->data)) {
            $this->data = $this->getFileManager()->getPhpContents($this->cacheFile);
        }

        return $this->data;
    }

}