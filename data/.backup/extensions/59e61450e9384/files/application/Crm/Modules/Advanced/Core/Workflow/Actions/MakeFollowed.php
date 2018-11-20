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

namespace Core\Modules\Advanced\Core\Workflow\Actions;

use Core\ORM\Entity;

class MakeFollowed extends BaseEntity
{
    protected function run(Entity $entity, array $actionData)
    {
        if (empty($actionData['userIdList'])) return;
        if (empty($actionData['whatToFollow'])) {
            $actionData['whatToFollow'] = 'targetEntity';
        }


        if (!is_array($actionData['userIdList'])) return;

        $userIdList = $actionData['userIdList'];

        $target = null;
        if ($actionData['whatToFollow'] == 'targetEntity') {
            $target = $entity;
        } else {
            $link = $actionData['whatToFollow'];
            $type = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.links.' . $link . '.type');

            if (empty($type)) return;

            $idField = $link . 'Id';

            if ($type == 'belongsTo') {
                if (!$entity->get($idField)) return;
                $foreignEntityType = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.links.' . $link . '.entity');
                if (empty($foreignEntityType)) return;
                $target = $this->getEntityManager()->getEntity($foreignEntityType, $entity->get($idField));
            } else if ($type == 'belongsToParent') {
                $typeField = $link . 'Type';
                if (!$entity->get($idField)) return;
                if (!$entity->get($typeField)) return;
                $target = $this->getEntityManager()->getEntity($entity->get($typeField), $entity->get($idField));
            }
            if (empty($target)) return;
        }

        $streamService = $this->getServiceFactory()->create('Stream');
        $streamService->followEntityMass($target, $userIdList);

        return true;
    }
}