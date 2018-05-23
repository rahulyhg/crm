<?php


namespace Core\Hooks\Common;

use Core\ORM\Entity;

class AssignmentEmailNotification extends \Core\Core\Hooks\Base
{
    public function afterSave(Entity $entity, array $options = [])
    {
        if (!empty($options['silent']) || !empty($options['noNotifications'])) {
            return;
        }
        if (
            $this->getConfig()->get('assignmentEmailNotifications')
            &&
            $entity->has('assignedUserId')
            &&
            in_array($entity->getEntityType(), $this->getConfig()->get('assignmentEmailNotificationsEntityList', []))
        ) {

            $userId = $entity->get('assignedUserId');
            if (!empty($userId) && $userId != $this->getUser()->id && $entity->isFieldChanged('assignedUserId')) {
                $job = $this->getEntityManager()->getEntity('Job');
                $job->set(array(
                    'serviceName' => 'EmailNotification',
                    'method' => 'notifyAboutAssignmentJob',
                    'data' => json_encode(array(
                        'userId' => $userId,
                        'assignerUserId' => $this->getUser()->id,
                        'entityId' => $entity->id,
                        'entityType' => $entity->getEntityName()
                    )),
                    'executeTime' => date('Y-m-d H:i:s'),
                ));
                $this->getEntityManager()->saveEntity($job);
            }
        }
    }

}

