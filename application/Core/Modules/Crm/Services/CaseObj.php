<?php


namespace Core\Modules\Crm\Services;

use \Core\ORM\Entity;

class CaseObj extends \Core\Services\Record
{
    protected $mergeLinkList = [
        'tasks',
        'meetings',
        'calls',
        'emails'
    ];

    protected $readOnlyAttributeList = [
        'inboundEmailId'
    ];

    public function afterCreate(Entity $entity, array $data = array())
    {
        parent::afterCreate($entity, $data);
        if (!empty($data['emailId'])) {
            $email = $this->getEntityManager()->getEntity('Email', $data['emailId']);
            if ($email && !$email->get('parentId')) {
                $email->set(array(
                    'parentType' => 'Case',
                    'parentId' => $entity->id
                ));
                $this->getEntityManager()->saveEntity($email);
            }
        }
    }

}

