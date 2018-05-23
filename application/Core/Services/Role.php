<?php


namespace Core\Services;

use \Core\ORM\Entity;

class Role extends Record
{
    protected function init()
    {
        parent::init();
        $this->addDependency('fileManager');
    }

    public function afterCreate(Entity $entity, array $data = array())
    {
        parent::afterCreate($entity, $data);
        $this->clearRolesCache();
    }

    public function afterUpdate(Entity $entity, array $data = array())
    {
        parent::afterUpdate($entity, $data);
        $this->clearRolesCache();
    }

    protected function clearRolesCache()
    {
        $this->getInjection('fileManager')->removeInDir('data/cache/application/acl');
    }
}

