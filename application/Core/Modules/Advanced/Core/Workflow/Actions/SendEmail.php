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

class SendEmail extends Base
{
    /**
     * Main run method
     *
     * @param  array $actionData
     * @return string
     */
    protected function run(Entity $entity, array $actionData)
    {
        $jobData = array(
            'workflowId' => $this->getWorkflowId(),
            'entityId' => $this->getEntity()->get('id'),
            'entityName' => $this->getEntity()->getEntityType(),
            'from' => $this->getEmailAddress('from'),
            'to' => $this->getEmailAddress('to'),
            'emailTemplateId' => isset($actionData['emailTemplateId']) ? $actionData['emailTemplateId'] : null,
            'doNotStore' => isset($actionData['doNotStore']) ? $actionData['doNotStore'] : false
        );

        if (is_null($jobData['to'])) {
            return;
        }

        $job = $this->getEntityManager()->getEntity('Job');
        $job->set(array(
            'serviceName' => 'Workflow',
            'method' => 'sendEmail',
            'data' => json_encode($jobData),
            'executeTime' => $this->getExecuteTime($actionData),
        ));

        return $this->getEntityManager()->saveEntity($job);
    }

    /**
     * Get email address defined in workflow
     *
     * @param  string $type
     * @return array
     */
    protected function getEmailAddress($type = 'to', $defaultReturn = null)
    {
        $data = $this->getActionData();
        $fieldValue = $data[$type];

        $returnData = null;

        switch ($fieldValue) {
            case 'specifiedEmailAddress':
                $returnData = array(
                    'email' => $data[$type . 'Email'],
                    'type' => $fieldValue,
                );
                break;

            case 'teamUsers':
            case 'followers':
            case 'followersExcludingAssignedUser':
                $entity = $this->getEntity();

                $returnData = array(
                    'entityName' => $entity->getEntityType(),
                    'entityId' => $entity->get('id'),
                    'type' => $fieldValue,
                );
                break;

            case 'specifiedTeams':
            case 'specifiedUsers':
            case 'specifiedContacts':
                $returnData = array(
                    'type' => $fieldValue,
                    'entityIds' => $data['toSpecifiedEntityIds'],
                    'entityName' => $data['toSpecifiedEntityName'],
                );
                break;

            case 'currentUser':
                $returnData = array(
                    'entityName' => $this->getContainer()->get('user')->getEntityType(),
                    'entityId' => $this->getContainer()->get('user')->get('id'),
                    'type' => $fieldValue,
                );
                break;

            case 'system':
                $returnData = array(
                    'type' => $fieldValue,
                );
                break;

            default:
                $fieldEntity = Utils::getFieldValue($this->getEntity(), $fieldValue, true, $this->getEntityManager());
                if ($fieldEntity instanceof \Core\ORM\Entity) {
                    if ($fieldEntity->hasAttribute('emailAddress') && $fieldEntity->getAttributeType('emailAddress') === 'email') {
                        $returnData = array(
                            'entityName' => $fieldEntity->getEntityType(),
                            'entityId' => $fieldEntity->get('id'),
                            'type' => $fieldValue,
                        );
                    }

                }
                break;
        }

        return (isset($returnData)) ? $returnData : $defaultReturn;
    }
}