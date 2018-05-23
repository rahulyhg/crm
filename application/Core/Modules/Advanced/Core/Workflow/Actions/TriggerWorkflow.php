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

class TriggerWorkflow extends Base
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
            'entityType' => $this->getEntity()->getEntityType(),
            'nextWorkflowId' => $actionData['workflowId'],
            'values' => $entity->getValues()
        );

        $executeTime = null;
        if ($actionData['execution']['type'] != 'immediately') {
            $executeTime = $this->getExecuteTime($actionData);
        }

        if ($executeTime) {
            $job = $this->getEntityManager()->getEntity('Job');

            $job->set(array(
                'serviceName' => 'Workflow',
                'method' => 'jobTriggerWorkflow',
                'data' => json_encode($jobData),
                'executeTime' => $executeTime,
            ));

            $this->getEntityManager()->saveEntity($job);
        } else {
            $service = $this->getServiceFactory()->create('Workflow');
            $service->triggerWorkflow($entity, $actionData['workflowId']);
        }

        return true;
    }
}