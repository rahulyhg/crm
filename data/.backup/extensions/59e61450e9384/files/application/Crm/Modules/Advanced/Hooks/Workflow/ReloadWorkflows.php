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

namespace Core\Modules\Advanced\Hooks\Workflow;

class ReloadWorkflows extends \Core\Core\Hooks\Base
{
    public static $order = 9;

    protected function init()
    {
        $this->dependencies[] = 'workflowManager';
    }

    protected function getWorkflowManager()
    {
        return $this->getInjection('workflowManager');
    }

    public function afterSave(\Core\Orm\Entity $entity)
    {
        $workflowManager = $this->getWorkflowManager();
        $workflowManager->loadWorkflows(true);
    }

    public function afterRemove(\Core\Orm\Entity $entity)
    {
        $workflowManager = $this->getWorkflowManager();
        $workflowManager->loadWorkflows(true);
    }

}