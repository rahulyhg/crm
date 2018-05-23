<?php


namespace Core\Core\FileStorage\Storages;

use \Core\Core\Interfaces\Injectable;

abstract class Base implements Injectable
{
    protected $dependencyList = [];

    protected $injections = array();

    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
    }

    protected function getInjection($name)
    {
        return $this->injections[$name];
    }

    protected function addDependency($name)
    {
        $this->dependencyList[] = $name;
    }

    protected function addDependencyList(array $list)
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }

    public function getDependencyList()
    {
        return $this->dependencyList;
    }

    abstract public function hasDownloadUrl(\Core\Entities\Attachment $attachment);

    abstract public function getDownloadUrl(\Core\Entities\Attachment $attachment);

    abstract public function unlink(\Core\Entities\Attachment $attachment);

    abstract public function getContents(\Core\Entities\Attachment $attachment);

    abstract public function isFile(\Core\Entities\Attachment $attachment);

    abstract public function putContents(\Core\Entities\Attachment $attachment, $contents);

    abstract public function getLocalFilePath(\Core\Entities\Attachment $attachment);
}
