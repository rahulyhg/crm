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

use Core\ORM\Entity;

class UpdateEntity extends BaseEntity
{
    protected function run(Entity $entity, array $actionData)
    {
        $entityManager = $this->getEntityManager();

        $reloadedEntity = $entityManager->getEntity($entity->getEntityType(), $entity->id);

        $data = $this->getDataToFill($reloadedEntity, $actionData['fields']);

        $reloadedEntity->set($data);
        $entity->set($data);

        if (!empty($actionData['formula'])) {
            $this->getFormulaManager()->run($actionData['formula'], $reloadedEntity, $this->getFormulaVariables($entity));
            $this->getFormulaManager()->run($actionData['formula'], $entity, $this->getFormulaVariables($entity));
        }

        return $entityManager->saveEntity($reloadedEntity, ['skipWorkflow' => true, 'skipModifiedBy' => true]);
    }
}