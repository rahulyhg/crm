<?php


namespace Core\Modules\Crm\Repositories;

use Core\ORM\Entity;

class Account extends \Core\Core\ORM\Repositories\RDB
{
    public function afterSave(Entity $entity, array $options = array())
    {
        parent::afterSave($entity, $options);

        if ($entity->has('targetListId')) {
        	$this->relate($entity, 'targetLists', $entity->get('targetListId'));
        }
    }

    protected function afterRelateContacts(Entity $entity, $foreign, $data, array $options = array())
    {
        if (!($foreign instanceof Entity)) return;

        if (!$foreign->get('accountId')) {
            $foreign->set('accountId', $entity->id);
            $this->getEntityManager()->saveEntity($foreign);
        }
    }

    protected function afterUnrelateContacts(Entity $entity, $foreign, array $options = array())
    {
        if (!($foreign instanceof Entity)) return;

        if ($foreign->get('accountId') && $foreign->get('accountId') === $entity->id) {
            $foreign->set('accountId', null);
            $this->getEntityManager()->saveEntity($foreign);
        }
    }
}

