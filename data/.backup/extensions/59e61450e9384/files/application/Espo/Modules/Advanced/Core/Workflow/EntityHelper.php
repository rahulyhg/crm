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

namespace Core\Modules\Advanced\Core\Workflow;

use Core\ORM\Entity;

class EntityHelper
{
    private $container;

    private $streamService;

    protected $entityDefsList = array();

    public function __construct(\Core\Core\Container $container)
    {
        $this->container = $container;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getEntityManager()
    {
        return $this->container->get('entityManager');
    }

    protected function getMetadata()
    {
        return $this->container->get('metadata');
    }

    protected function getUser()
    {
        return $this->container->get('user');
    }

    protected function getEntiyDefs($entityType, $category = null)
    {
        if ($entityType instanceof Entity) {
            $entityType = $entityType->getEntityType();
        }

        if (!isset($this->entityDefsList[$entityType])) {
            $this->entityDefsList[$entityType] = $this->getMetadata()->get('entityDefs.' . $entityType);
        }

        if (isset($category)) {
            return isset($this->entityDefsList[$entityType][$category]) ? $this->entityDefsList[$entityType][$category] : null;
        }

        return $this->entityDefsList[$entityType];
    }

    /**
     * Normalize field name for fields and relations
     *
     * @param  \Core\Orm\Entity $entity
     * @param  string           $fieldName
     *
     * @return string
     */
    public function normalizeFieldName(Entity $entity, $fieldName)
    {
        if ($entity->hasRelation($fieldName)) {
            $relations = $entity->getRelations();
            $relation = $relations[$fieldName];
            switch ($relation['type']) {
                case 'hasChildren':
                    if (isset($relation['foreignKey'])) {
                        $fieldName = $relation['foreignKey'];
                    }
                    break;

                case 'belongsTo':
                    if (isset($relation['key'])) {
                        $fieldName = $relation['key'];
                    }
                    break;

                case 'belongsToParent':
                    $fieldName = array( //order of this array is important
                        $fieldName . 'Id',
                        $fieldName . 'Type',
                    );
                    break;

                case 'hasMany':
                case 'manyMany':
                    $fieldName .= 'Ids';
                    break;
            }
        }

        $fieldDefs = $this->getEntiyDefs($entity, 'fields');

        if (isset($fieldDefs[$fieldName])) {
            switch ($fieldDefs[$fieldName]['type']) {
                case 'linkParent':
                    $fieldName .= 'Id';
                    break;

                case 'currency':
                    $fieldName = array(
                        $fieldName,
                        $fieldName . 'Currency',
                    );
                    break;
            }
        }

        return $fieldName;
    }

    /**
     *  todo: REWRITE
     */
    protected function getRelevantFieldMap(Entity $entity1, Entity $entity2, $field1, $field2)
    {
        $normalizedFieldName1 = (array) $this->normalizeFieldName($entity1, $field1);
        $normalizedFieldName2 = (array) $this->normalizeFieldName($entity2, $field2);

        $relevantFields = array();
        if (count($normalizedFieldName1) == count($normalizedFieldName2)) {
            foreach ($normalizedFieldName1 as $key => $name) {
                $relevantFields[$name] = $normalizedFieldName2[$key];
            }
        }

        return $relevantFields;
    }

    /**
     * Get field value for a field/related field. If this field has a relation, get value from the relation
     *
     * @param  Entity $fromEntity
     * @param  Entity $toEntity
     * @param  string $fromField
     * @param  string $toField
     *
     * @return array
     */
    public function getFieldValues(Entity $fromEntity, Entity $toEntity, $fromField, $toField)
    {
        $entity = $fromEntity;
        $fieldName = $fromField;

        $values = array();

        //Get field names, 0 - field name or relation name, 1 - related field name
        if (strstr($fieldName, '.')) {
            list($entityFieldName, $relatedEntityFieldName) = explode('.', $fieldName);
            $relatedEntity = $entity->get($entityFieldName);

            //if $entity is just created and doesn't have added relations
            if (!isset($relatedEntity) && $entity->hasRelation($entityFieldName)) {
                $relations = $entity->getRelations();
                $relation = $relations[$entityFieldName];

                $normalizedEntityFieldName = $this->normalizeFieldName($entity, $entityFieldName);
                if (isset($relation['entity']) && $entity->hasField($normalizedEntityFieldName) && $entity->get($normalizedEntityFieldName)) {
                    $relatedEntity = $this->getEntityManager()->getEntity($relation['entity'], $entity->get($normalizedEntityFieldName));
                }
            }

            if ($relatedEntity instanceof \Core\ORM\Entity) {
                $entity = $relatedEntity;
                $fieldName = $relatedEntityFieldName;
            } else {
                $GLOBALS['log']->error('Workflow [getValuesForRelevantFields]: The related field ['.$fieldName.'] entity ['.$entity->getEntityName().'] has unsupported instance ['.(isset($relatedEntity) ? get_class($relatedEntity) : var_export($relatedEntity, true)).'].');
                return $values;
            }
        }

        if ($entity->hasRelation($fieldName)) {
            //load field values
            if (!$entity->isNew()) {
                switch ($entity->getRelationType($fieldName)) { //ORM types
                    case 'manyMany':
                    case 'hasChildren':
                        $entity->loadLinkMultipleField($fieldName);
                        break;

                    case 'belongsTo':
                    case 'hasOne':
                        $entity->loadLinkField($fieldName);
                        break;
                }
            }
        }

        $fieldMap = $this->getRelevantFieldMap($entity, $toEntity, $fieldName, $toField);

        foreach ($fieldMap as $fromFieldName => $toFieldName) {
            $values[$toFieldName] = $entity->get($fromFieldName);
        }

        return $values;
    }

    protected function getRelatedEntity(Entity $entity, $fieldName)
    {
        if ($entity->getRelationType($fieldName) === 'belongsToParent') {
            if ($entity->get($fieldName . 'Type') && $entity->get($fieldName . 'Id')) {
                return $this->getEntityManager()->getEntity($entity->get($fieldName . 'Type'), $entity->get($fieldName . 'Id'));
            }
        }

        return $entity->get($fieldName);
    }
}