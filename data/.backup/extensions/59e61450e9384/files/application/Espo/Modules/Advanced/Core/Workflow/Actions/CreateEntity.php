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

use Core\Modules\Advanced\Core\Workflow\Utils;

use Core\ORM\Entity;

class CreateEntity extends BaseEntity
{
    protected function run(Entity $entity, array $actionData)
    {
        $entityManager = $this->getEntityManager();

        $linkEntityName = $actionData['link'];

        $GLOBALS['log']->debug('Workflow\Actions\\'.$actionData['type'].': Start creating a new entity ['.$linkEntityName.'].');

        $newEntity = $entityManager->getEntity($linkEntityName);

        $data = $this->getDataToFill($newEntity, $actionData['fields']);
        $newEntity->set($data);

        if (!empty($actionData['formula'])) {
            $this->getFormulaManager()->run($actionData['formula'], $newEntity, $this->getFormulaVariables($entity));
        }

        if (!$newEntity->has('createdById')) {
            $newEntity->set('createdById', 'system');
        }

        $result = $entityManager->saveEntity($newEntity, array('skipCreatedBy' => true));

        $GLOBALS['log']->debug('Workflow\Actions\\'.$actionData['type'].': End creating a new entity ['.$linkEntityName.', '.$newEntity->id.'].');

        return $result;
    }
}
