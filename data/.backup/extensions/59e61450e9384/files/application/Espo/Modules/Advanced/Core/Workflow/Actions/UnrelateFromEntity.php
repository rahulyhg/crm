<?php
/*********************************************************************************
 * The contents of this file are subject to the Samex CRM Advanced
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

use \Core\ORM\Entity;

class UnrelateFromEntity extends BaseEntity
{
    protected function run(Entity $entity, array $actionData)
    {
        $entityManager = $this->getEntityManager();

        if (empty($actionData['entityId']) || empty($actionData['link'])) {
            throw new Error('Workflow['.$this->getWorkflowId().']: Bad params defined for UnrelateFromEntity');
        }

        $foreignEntityType = $entity->getRelationParam($actionData['link'], 'entity');

        if (!$foreignEntityType) {
            throw new Error('Workflow['.$this->getWorkflowId().']: Could not find foreign entity type for UnrelateFromEntity');
        }

        $foreignEntity = $this->getEntityManager()->getEntity($foreignEntityType, $actionData['entityId']);

        if (!$foreignEntity) {
            throw new Error('Workflow['.$this->getWorkflowId().']: Could not find foreign entity for UnrelateFromEntity');;
        }

        $entityManager->getRepository($entity->getEntityType())->unrelate($entity, $actionData['link'], $foreignEntity);

        return true;
    }
}
