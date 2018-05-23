<?php


namespace Core\Modules\Crm\Services;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\ORM\Entity;

class Target extends \Core\Services\Record
{
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

    public function convert($id)
    {
        $entityManager = $this->getEntityManager();
        $target = $this->getEntity($id);

        if (!$this->getAcl()->check($target, 'delete')) {
            throw new Forbidden();
        }
        if (!$this->getAcl()->check('Lead', 'read')) {
            throw new Forbidden();
        }

        $lead = $entityManager->getEntity('Lead');
        $lead->set($target->toArray());

        $entityManager->removeEntity($target);
        $entityManager->saveEntity($lead);

        return $lead;
    }
}

