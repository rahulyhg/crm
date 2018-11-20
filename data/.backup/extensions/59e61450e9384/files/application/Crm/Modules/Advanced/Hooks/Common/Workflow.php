<?php
/*********************************************************************************
 * The contents of this file are subject to the CRM Advanced
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

namespace Core\Modules\Advanced\Hooks\Common;

use Core\Modules\Advanced\Core\WorkflowManager;

class Workflow extends \Core\Core\Hooks\Base
{
    public static $order = 9;

    protected function init()
    {
        $this->addDependency('workflowManager');
    }

    protected function getWorkflowManager()
    {
        return $this->getInjection('workflowManager');
    }

    public function afterSave(\Core\ORM\Entity $entity, array $options = array())
    {
        $workflowManager = $this->getWorkflowManager();

        if (!empty($options['skipWorkflow'])) {
            return;
        }

        if (!$entity->isFetched()) {
            $workflowManager->process($entity, WorkflowManager::AFTER_RECORD_CREATED);
        }

        $workflowManager->process($entity, WorkflowManager::AFTER_RECORD_SAVED);
    }
}