<?php


namespace Core\Modules\Crm\Services;

use \Core\ORM\Entity;

class Contact extends \Core\Services\Record
{

    protected $readOnlyAttributeList = [
        'inboundEmailId',
        'portalUserId'
    ];

    protected $exportAllowedAttributeList = [
        'title'
    ];

    protected function getDuplicateWhereClause(Entity $entity, $data = array())
    {
        $data = array(
            'OR' => array(
                array(
                    'firstName' => $entity->get('firstName'),
                    'lastName' => $entity->get('lastName'),
                )
            )
        );
        if (
            ($entity->get('emailAddress') || $entity->get('emailAddressData'))
            &&
            ($entity->isNew() || $entity->isFieldChanged('emailAddress') || $entity->isFieldChanged('emailAddressData'))
        ) {
            if ($entity->get('emailAddress')) {
                $list = [$entity->get('emailAddress')];
            }
            if ($entity->get('emailAddressData')) {
                foreach ($entity->get('emailAddressData') as $row) {
                    if (!in_array($row->emailAddress, $list)) {
                        $list[] = $row->emailAddress;
                    }
                }
            }
            foreach ($list as $emailAddress) {
                $data['OR'][] = array(
                    'emailAddress' => $emailAddress
                );
            }
        }

        return $data;
    }

    public function afterCreate(Entity $entity, array $data = array())
    {
        parent::afterCreate($entity, $data);
        if (!empty($data['emailId'])) {
            $email = $this->getEntityManager()->getEntity('Email', $data['emailId']);
            if ($email && !$email->get('parentId')) {
                if ($this->getConfig()->get('b2cMode')) {
                    $email->set(array(
                        'parentType' => 'Contact',
                        'parentId' => $entity->id
                    ));
                } else {
                    if ($entity->get('accountId')) {
                        $email->set(array(
                            'parentType' => 'Account',
                            'parentId' => $entity->get('accountId')
                        ));
                    }
                }
                $this->getEntityManager()->saveEntity($email);
            }
        }
    }
}

