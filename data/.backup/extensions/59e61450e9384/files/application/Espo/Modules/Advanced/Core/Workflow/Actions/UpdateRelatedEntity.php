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
use Core\ORM\EntityCollection;

class UpdateRelatedEntity extends BaseEntity
{
    protected function run(Entity $entity, array $actionData)
    {
        $link = $actionData['link'];
        $relationDefs = $entity->getRelations();

        $relatedEntities = $this->getRelatedEntities($entity, $link);

        foreach ($relatedEntities as $relatedEntity) {
            if (!($relatedEntity instanceof \Core\ORM\Entity)) {
                continue;
            }

            $update = true;

            if (isset($relationDefs[$link]) && $relationDefs[$link]['type'] == 'belongsToParent' && !empty($actionData['parentEntity'])) {
                if ($actionData['parentEntity'] != $relatedEntity->getEntityType()) {
                    $update = false;
                }
            }

            if ($update) {
                $data = $this->getDataToFill($relatedEntity, $actionData['fields']);
                $relatedEntity->set($data);

                if (!empty($actionData['formula'])) {
                    $this->getFormulaManager()->run($actionData['formula'], $relatedEntity, $this->getFormulaVariables($entity));
                }

                if (!$relatedEntity->has('modifiedById')) {
                    $relatedEntity->set('modifiedById', 'system');
                    $relatedEntity->set('modifiedByName', 'System');
                }
                $this->getEntityManager()->saveEntity($relatedEntity, array('skipModifiedBy' => true));
            }
        }

        return true;
    }

    /**
     * Get Related Entity
     *
     * @param  \Core\ORM\Entity $entity
     * @param  string $link
     *
     * @return \Core\ORM\EntityCollection
     */
    protected function getRelatedEntities(Entity $entity, $link)
    {
        if (empty($link) || !$entity->hasRelation($link)) {
            return;
        }

        $relationDefs = $entity->getRelations();
        $linkDefs = $relationDefs[$link];

        $relatedEntities = array();

        switch ($linkDefs['type']) {
            case 'belongsToParent':
                $parentType = $entity->get($link . 'Type');
                $parentId = $entity->get($link . 'Id');
                if (!empty($parentType) && !empty($parentId)) {
                    try {
                        $relatedEntity = $this->getEntityManager()->getEntity($parentType, $parentId);
                    } catch (\Exception $e) {
                        $GLOBALS['log']->info('Workflow[UpdateRelatedEntity]: Cannot getRelatedEntities(), error: '. $e->getMessage());
                    }

                    $relatedEntities = $this->getEntityManager()->createCollection($entity->getEntityType(), array($relatedEntity));
                }
                break;

            case 'hasMany':
            case 'hasChildren':
                $relatedEntities = $this->getEntityManager()->getRepository($entity->getEntityType())->findRelated($entity, $link);
                break;

            default:
                try {
                    $relatedEntities = $entity->get($link);
                } catch (\Exception $e) {
                    $GLOBALS['log']->info('Workflow[UpdateRelatedEntity]: Cannot getRelatedEntities(), error: '. $e->getMessage());
                }
                break;
        }

        if (!($relatedEntities instanceof EntityCollection)) {
            $relatedEntities = $this->getEntityManager()->createCollection($entity->getEntityType(), array($relatedEntities));
        }

        return $relatedEntities;
    }
}