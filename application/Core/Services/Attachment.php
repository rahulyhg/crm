<?php


namespace Core\Services;

use \Core\ORM\Entity;

class Attachment extends Record
{
    protected $notFilteringAttributeList = ['contents'];

    public function createEntity($data)
    {
        if (!empty($data['file'])) {
            list($prefix, $contents) = explode(',', $data['file']);
            $contents = base64_decode($contents);
            $data['contents'] = $contents;
        }

        $entity = parent::createEntity($data);

        if (!empty($data['file'])) {
            $entity->clear('contents');
        }

        return $entity;
    }

    protected function beforeCreate(Entity $entity, array $data = array())
    {
        $storage = $entity->get('storage');
        if ($storage && !$this->getMetadata()->get(['app', 'fileStorage', 'implementationClassNameMap', $storage])) {
            $entity->clear('storage');
        }
    }

    protected function beforeUpdate(Entity $entity, array $data = array())
    {
        $storage = $entity->get('storage');
        if ($storage && !$this->getMetadata()->get(['app', 'fileStorage', 'implementationClassNameMap', $storage])) {
            $entity->clear('storage');
        }
    }
}

