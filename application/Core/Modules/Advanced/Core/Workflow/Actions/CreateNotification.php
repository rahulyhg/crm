<?php
/*********************************************************************************
 * The contents of this file are subject to the CoreCRM Advanced
 * Agreement ("License") which can be viewed at
 * http://www.espocrm.com/advanced-pack-agreement.
 * By installing or using this file, You have unconditionally agreed to the
 * terms and conditions of the License, and You may not use this file except in
 * compliance with the License.  Under the terms of the license, You shall not,
 * sublicense, resell, rent, lease, distribute, or otherwise  transfer rights
 * or usage to the software.
 * 
 * License ID: bcac485dee9efd0f36cf6842ad5b69b4
 ***********************************************************************************/

namespace Core\Modules\Advanced\Core\Workflow\Actions;

use Core\Core\Exceptions\Error;
use Core\Modules\Advanced\Core\Workflow\Utils;

use Core\ORM\Entity;

class CreateNotification extends Base
{
    /**
     * Main run method
     *
     * @param  array $actionData
     * @return string
     */
    protected function run(Entity $entity, array $actionData)
    {
        if (empty($actionData['recipient'])) {
            return;
        }
        if (empty($actionData['messageTemplate'])) {
            return;
        }

        $userList = [];
        switch ($actionData['recipient']) {
            case 'specifiedUsers':
                if (empty($actionData['userIdList']) || !is_array($actionData['userIdList'])) {
                    return;
                }
                $userIds = $actionData['userIdList'];
                break;

            case 'specifiedTeams':
                $userIds = $this->getHelper()->getUserIdsByTeamIds($actionData['specifiedTeamsIds']);
                break;

            case 'teamUsers':
                $entity->loadLinkMultipleField('teams');
                $userIds = $this->getHelper()->getUserIdsByTeamIds($entity->get('teamsIds'));
                break;

            case 'followers':
                $userIds = $this->getHelper()->getFollowerUserIds($entity);
                break;

            case 'followersExcludingAssignedUser':
                $userIds = $this->getHelper()->getFollowerUserIdsExcludingAssignedUser($entity);
                break;

            default:
                $user = $this->getRecipientUser($actionData['recipient']);
                if ($user) {
                    $userList[] = $user;
                }
                break;
        }

        if (isset($userIds)) {
            foreach ($userIds as $userId) {
                $user = $this->getEntityManager()->getEntity('User', $userId);
                $userList[] = $user;
            }
        }

        foreach ($userList as $user) {
            $notification = $this->getEntityManager()->getEntity('Notification');
            $notification->set(array(
                'type' => 'message',
                'data' => array(
                    'entityId' => $entity->id,
                    'entityType' => $entity->getEntityType(),
                    'entityName' => $entity->get('name'),
                    'userId' => $this->getUser()->id,
                    'userName' => $this->getUser()->get('name')
                ),
                'userId' => $user->id,
                'message' => $actionData['messageTemplate'],
                'relatedId' => $entity->id,
                'relatedType' => $entity->getEntityType()
            ));
            $this->getEntityManager()->saveEntity($notification);
        }
        return true;
    }


    /**
     * Get email address defined in workflow
     *
     * @param  string $type
     * @return array
     */
    protected function getRecipientUser($recipient)
    {
        $data = $this->getActionData();

        if ($recipient == 'currentUser') {
            return $this->getUser();
        } else {
            return Utils::getFieldValue($this->getEntity(), $recipient, true, $this->getEntityManager());
        }

    }
}