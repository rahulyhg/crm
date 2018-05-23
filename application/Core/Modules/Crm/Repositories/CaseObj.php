<?php


namespace Core\Modules\Crm\Repositories;

use Core\ORM\Entity;

class CaseObj extends \Core\Core\ORM\Repositories\RDB
{
    protected function init()
    {
        parent::init();
        $this->addDependency('serviceFactory');
    }

    public function afterSave(Entity $entity, array $options = array())
    {
        $result = parent::afterSave($entity, $options);
        $this->handleAfterSaveContacts($entity, $options);
        return $result;
    }

    protected function handleAfterSaveContacts(Entity $entity, array $options = array())
    {
        $contactIdChanged = $entity->has('contactId') && $entity->get('contactId') != $entity->getFetched('contactId');

        if ($contactIdChanged) {
            $contactId = $entity->get('contactId');

            if ($entity->getFetched('contactId')) {
                $previousPortalUser = $this->getEntityManager()->getRepository('User')->where(array(
                    'contactId' => $entity->getFetched('contactId'),
                    'isPortal' => true
                ))->findOne();
                if ($previousPortalUser) {
                    $this->getInjection('serviceFactory')->create('Stream')->unfollowEntity($entity, $previousPortalUser->id);
                }
            }

            if (empty($contactId)) {
                $this->unrelate($entity, 'contacts', $entity->getFetched('contactId'));
                return;
            }

            $portalUser = $this->getEntityManager()->getRepository('User')->where(array(
                'contactId' => $contactId,
                'isPortal' => true,
                'isActive' => true
            ))->findOne();
            if ($portalUser) {
                $this->getInjection('serviceFactory')->create('Stream')->followEntity($entity, $portalUser->id);
            }
        }

        if ($contactIdChanged) {
            $pdo = $this->getEntityManager()->getPDO();

            $sql = "
                SELECT id FROM case_contact
                WHERE
                    contact_id = ".$pdo->quote($contactId)." AND
                    case_id = ".$pdo->quote($entity->id)." AND
                    deleted = 0
            ";
            $sth = $pdo->prepare($sql);
            $sth->execute();

            if (!$sth->fetch()) {
                $this->relate($entity, 'contacts', $contactId);
            }
        }
    }
}

