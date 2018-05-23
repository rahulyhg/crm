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

use Core\ORM\Entity;
use Core\Core\Exceptions\Error;

class ApplyAssignmentRule extends BaseEntity
{
    protected function run(Entity $entity, array $actionData)
    {
        $entityManager = $this->getEntityManager();

        if (!$entity->hasAttribute('assignedUserId') || !$entity->hasRelation('assignedUser')) return;

        $reloadedEntity = $entityManager->getEntity($entity->getEntityType(), $entity->id);

        if (empty($actionData['targetTeamId']) || empty($actionData['assignmentRule'])) {
            $GLOBALS['log']->error('AssignmentRule: Not enough parameters.');
            throw new Error();
        }

        $targetTeamId = $actionData['targetTeamId'];
        $assignmentRule = $actionData['assignmentRule'];

        $targetUserPosition = null;
        if (!empty($actionData['targetUserPosition'])) {
            $targetUserPosition = $actionData['targetUserPosition'];
        }

        $listReportId = null;
        if (!empty($actionData['listReportId'])) {
            $listReportId = $actionData['listReportId'];
        }

        if (!in_array($assignmentRule, $this->getMetadata()->get('entityDefs.Workflow.assignmentRuleList', []))) {
            $GLOBALS['log']->error('AssignmentRule: ' . $assignmentRule . ' is not supported.');
            throw new Error();
        }

        $className = '\\Core\\Custom\\Business\\Workflow\\AssignmentRules\\' . str_replace('-', '', $assignmentRule);
        if (!class_exists($className)) {
            $className = '\\Core\\Modules\\Advanced\\Business\\Workflow\\AssignmentRules\\' . str_replace('-', '', $assignmentRule);
            if (!class_exists($className)) {
                $GLOBALS['log']->error('AssignmentRule: Class ' . $className . ' not found.');
                throw new Error();
            }
        }

        $selectManager = $this->getContainer()->get('selectManagerFactory')->create($entity->getEntityType());
        $reportService = $this->getContainer()->get('serviceFactory')->create('Report');

        $rule = new $className($entityManager, $selectManager, $reportService, $entity->getEntityType());

        $attributes = $rule->getAssignmentAttributes($entity, $targetTeamId, $targetUserPosition, $listReportId);

        $entity->set($attributes);
        $reloadedEntity->set($attributes);

        return $entityManager->saveEntity($reloadedEntity, [
            'skipWorkflow' => true,
            'noStream' => true,
            'noNotifications' => true,
            'skipModifiedBy' => true,
            'skipCreatedBy' => true
        ]);
    }
}