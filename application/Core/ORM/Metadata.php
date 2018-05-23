<?php


namespace Core\ORM;

class Metadata
{
    protected $data = array();

    public function setData($data)
    {
        $this->data = $data;
    }

    public function get($entityType)
    {
        if (!array_key_exists($entityType, $this->data)) {
            return null;
        }
        return $this->data[$entityType];
    }
}
