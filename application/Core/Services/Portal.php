<?php


namespace Core\Services;

use \Core\ORM\Entity;

class Portal extends Record
{
    protected $getEntityBeforeUpdate = true;

    public function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);
        $this->loadUrlField($entity);
    }

    public function loadAdditionalFieldsForList(Entity $entity)
    {
        parent::loadAdditionalFieldsForList($entity);
        $this->loadUrlField($entity);
    }

    protected function afterUpdate(Entity $entity, array $data = array())
    {
        parent::afterUpdate($entity, $data);
        $this->loadUrlField($entity);
    }

    protected function loadUrlField(Entity $entity)
    {
        $this->getRepository()->loadUrlField($entity);
    }
}

