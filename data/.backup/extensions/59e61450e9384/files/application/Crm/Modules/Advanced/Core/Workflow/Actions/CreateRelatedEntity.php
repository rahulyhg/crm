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

use \Core\ORM\Entity;

class CreateRelatedEntity extends CreateEntity
{
    protected function run(Entity $entity, array $actionData)
    {
        $entityManager = $this->getEntityManager();

        $linkEntityName = $this->getLinkEntityName($entity, $actionData['link']);

        if (!isset($linkEntityName)) {
            $GLOBALS['log']->error('Workflow\Actions\\'.$actionData['type'].': Cannot find an entity name of the link ['.$actionData['link'].'] in the entity ['.$entity->getEntityType().'].');
            return false;
        }

        $GLOBALS['log']->debug('Workflow\Actions\\'.$actionData['type'].': Start creating a new entity ['.$linkEntityName.'].');

        $newEntity = $entityManager->getEntity($linkEntityName);

        $data = $this->getDataToFill($newEntity, $actionData['fields']);
        $newEntity->set($data);

        if (!empty($actionData['formula'])) {
            $this->getFormulaManager()->run($actionData['formula'], $newEntity, $this->getFormulaVariables($entity));
        }

        $link = $actionData['link'];

        $isRelated = false;

        $foreignLink = $entity->getRelationParam($link, 'foreign');

        if ($foreignLink = $entity->getRelationParam($link, 'foreign')) {
            $foreignRelationType = $newEntity->getRelationType($foreignLink);
            if (in_array($foreignRelationType, ['belongsTo', 'belongsToParent'])) {
                if ($foreignRelationType === 'belongsTo') {
                    $newEntity->set($foreignLink. 'Id', $entity->id);
                    $isRelated = true;
                } else if ($foreignRelationType === 'belongsToParent') {
                    $newEntity->set($foreignLink. 'Id', $entity->id);
                    $newEntity->set($foreignLink. 'Type', $entity->getEntityType());
                    $isRelated = true;
                }
            }
        }

        if (!$newEntity->has('createdById')) {
            $newEntity->set('createdById', 'system');
        }

        $newEntityId = $entityManager->saveEntity($newEntity, array('skipCreatedBy' => true));

        if (!empty($newEntityId)) {
            $newEntity = $entityManager->getEntity($newEntity->getEntityType(), $newEntityId);

            $GLOBALS['log']->debug('Workflow\Actions\\'.$actionData['type'].': End creating a new entity ['.$newEntity->getEntityType().'] with ID ['.$newEntityId.'].');

            if (!$isRelated) {
                $GLOBALS['log']->debug('Workflow\Actions\\'.$actionData['type'].': Start relate entity ['.$entity->getEntityType().', '.$entity->id.'] with a new entity ['.$newEntity->getEntityType().', '.$newEntity->id.'] by link ['.$actionData['link'].'].');
                $entityManager->getRepository($entity->getEntityType())->relate($entity, $actionData['link'], $newEntity);
                $GLOBALS['log']->debug('Workflow\Actions\\'.$actionData['type'].': End relate entity ['.$entity->getEntityType().', '.$entity->id.'] with a new entity ['.$newEntity->getEntityType().', '.$newEntity->id.'] by link ['.$actionData['link'].'].');
            }
        }

        return !empty($newEntityId) ? true: false;
    }

    /**
     * Get an Entity name of a link
     *
     * @param  \Core\ORM\Entity $entity
     * @param  string $linkName
     *
     * @return string | null
     */
    protected function getLinkEntityName(Entity $entity, $linkName)
    {
        $linkEntity = $entity->get($linkName);
        if ($linkEntity instanceof Entity) {
            return $linkEntity->getEntityType();
        }

        if (!isset($linkEntityName) && $entity->hasRelation($linkName)) {
            $relations = $entity->getRelations();
            if (!empty($relations[$linkName]['entity'])) {
                return $relations[$linkName]['entity'];
            }
        }
    }
}
