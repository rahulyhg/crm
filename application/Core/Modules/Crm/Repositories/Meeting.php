<?php


namespace Core\Modules\Crm\Repositories;

use Core\ORM\Entity;
use Core\Core\Utils\Util;

class Meeting extends \Core\Core\Repositories\Event
{
    protected function beforeSave(Entity $entity, array $options = array())
    {
        $parentId = $entity->get('parentId');
        $parentType = $entity->get('parentType');
        if (!empty($parentId) || !empty($parentType)) {
            $parent = $this->getEntityManager()->getEntity($parentType, $parentId);
            if (!empty($parent)) {
                $accountId = null;
                if ($parent->getEntityType() == 'Account') {
                    $accountId = $parent->id;
                } else if ($parent->get('accountId')) {
                    $accountId = $parent->get('accountId');
                } else if ($parent->getEntityType() == 'Lead') {
                    if ($parent->get('status') == 'Converted') {
                        if ($parent->get('createdAccountId')) {
                            $accountId = $parent->get('createdAccountId');
                        }
                    }
                }
                if (!empty($accountId)) {
                    $entity->set('accountId', $accountId);
                }
            }
        }

        if (!$entity->isNew()) {
            if ($entity->isFieldChanged('dateStart') && $entity->isFieldChanged('dateStart') && !$entity->isFieldChanged('dateEnd')) {
                $dateEndPrevious = $entity->getFetched('dateEnd');
                $dateStartPrevious = $entity->getFetched('dateStart');
                if ($dateStartPrevious && $dateEndPrevious) {
                    $dtStart = new \DateTime($dateStartPrevious);
                    $dtEnd = new \DateTime($dateEndPrevious);
                    $dt = new \DateTime($entity->get('dateStart'));

                    if ($dtStart && $dtEnd && $dt) {
                        $duration = ($dtEnd->getTimestamp() - $dtStart->getTimestamp());
                        $dt->modify('+' . $duration . ' seconds');
                        $dateEnd = $dt->format('Y-m-d H:i:s');
                        $entity->set('dateEnd', $dateEnd);
                    }
                }
            }
        }

        parent::beforeSave($entity, $options);

        $assignedUserId = $entity->get('assignedUserId');
        if ($assignedUserId) {
            if ($entity->has('usersIds')) {
                $usersIds = $entity->get('usersIds');
                if (!is_array($usersIds)) {
                    $usersIds = [];
                }
                if (!in_array($assignedUserId, $usersIds)) {
                    $usersIds[] = $assignedUserId;
                    $entity->set('usersIds', $usersIds);
                    $hash = $entity->get('usersNames');
                    if ($hash instanceof \StdClass) {
                        $hash->$assignedUserId = $entity->get('assignedUserName');
                        $entity->set('usersNames', $hash);
                    }
                }
            } else {
                $entity->addLinkMultipleId('users', $assignedUserId);
            }
            if ($entity->isNew()) {
                $currentUserId = $this->getEntityManager()->getUser()->id;
                if (isset($usersIds) && in_array($currentUserId, $usersIds)) {
                    $usersColumns = $entity->get('usersColumns');
                    if (empty($usersColumns)) {
                        $usersColumns = new \StdClass();
                    }
                    if ($usersColumns instanceof \StdClass) {
                        if (empty($usersColumns->$currentUserId) || !($usersColumns->$currentUserId instanceof \StdClass)) {
                            $usersColumns->$currentUserId = new \StdClass();
                        }
                        if (empty($usersColumns->$currentUserId->status)) {
                            $usersColumns->$currentUserId->status = 'Accepted';
                        }
                    }
                }
            }
        }
    }
}
