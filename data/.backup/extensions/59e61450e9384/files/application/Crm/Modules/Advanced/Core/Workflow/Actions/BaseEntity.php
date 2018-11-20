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

use Core\Core\Exceptions\Error;
use Core\Modules\Advanced\Core\Workflow\Utils;
use Core\ORM\Entity;
use Core\Core\Utils\Util;

abstract class BaseEntity extends Base
{
    /**
     * Default fields, use only if not defined in a rule
     *
     * @var array
     */
    protected $defaultFields = array(
        'assignedUser',
        'teams',
    );

    protected $entityDefsList = array();

    protected function getEntiyDefs($entityType)
    {
        if (!isset($this->entityDefsList[$entityType])) {
            $this->entityDefsList[$entityType] = $this->getMetadata()->get('entityDefs.' . $entityType);
        }

        return $this->entityDefsList[$entityType];
    }

    /**
     * Get value of a field by $fieldName
     *
     * @param  string $fieldName
     * @param  \Core\Orm\Entity $filledEntity
     * @return mixed
     */
    protected function getValue($fieldName, \Core\Orm\Entity $filledEntity = null)
    {
        $entityHelper = $this->getEntityHelper();

        $actionData = $this->getActionData();
        $entity = $this->getEntity();

        if (isset($actionData['fields'][$fieldName])) {
            $fieldParams = $actionData['fields'][$fieldName];

            if (isset($filledEntity)) {
                $filledFieldType = Utils::getFieldType($filledEntity, $fieldName);
            }

            switch ($fieldParams['subjectType']) {
                case 'value':
                    if (isset($fieldParams['attributes']) && is_array($fieldParams['attributes'])) {

                        $filledEntity = isset($filledEntity) ? $filledEntity : $entity;

                        $fieldValue = $fieldParams['attributes'];
                        if (!isset($fieldParams['attributes'][$fieldName])) {
                            $normalizedFieldName = $entityHelper->normalizeFieldName($filledEntity, $fieldName);
                            if (!is_array($normalizedFieldName)) {
                                $normalizedFieldName = (array) $normalizedFieldName;
                            }

                            $fieldValue = array();
                            foreach ($normalizedFieldName as $name) {
                                if (isset($fieldParams['attributes'][$name])) {
                                    $fieldValue[$name] = $fieldParams['attributes'][$name];
                                }
                            }
                        }

                        foreach ($fieldValue as $rowFieldName => &$rowFieldValue) {
                            switch ($filledEntity->getAttributeType($rowFieldName)) {
                                case 'jsonArray':
                                    $rowFieldValue = (array) Util::arrayToObject($rowFieldValue);
                                    break;

                                case 'jsonObject':
                                    $rowFieldValue = Util::arrayToObject($rowFieldValue);
                                    break;
                            }
                        }
                    }
                    break;

                case 'field':
                    $filledEntity = isset($filledEntity) ? $filledEntity : $entity;
                    $fieldValue = $entityHelper->getFieldValues($entity, $filledEntity, $fieldParams['field'], $fieldName);

                    if (isset($fieldParams['shiftDays'])) {
                        $shiftUnit = 'days';
                        if (!empty($fieldParams['shiftUnit'])) {
                            $shiftUnit = $fieldParams['shiftUnit'];
                        }
                        if (!in_array($shiftUnit, ['hours', 'minutes', 'days', 'months'])) {
                            $shiftUnit = 'days';
                        }

                        $fieldValue = isset($fieldValue[$fieldName]) ? $fieldValue[$fieldName] : null;
                        $fieldValue = Utils::shiftDays($fieldParams['shiftDays'], $fieldValue, $filledFieldType, $shiftUnit);
                    }
                    break;

                case 'today':
                    $shiftUnit = 'days';
                    if (!empty($fieldParams['shiftUnit'])) {
                        $shiftUnit = $fieldParams['shiftUnit'];
                    }
                    if (!in_array($shiftUnit, ['hours', 'minutes', 'days', 'months'])) {
                        $shiftUnit = 'days';
                    }
                    return Utils::shiftDays($fieldParams['shiftDays'], null, $filledFieldType, $shiftUnit);
                    break;

                default:
                    throw new Error('Workflow['.$this->getWorkflowId().']: Unknown fieldName for a field [' . $fieldName . ']');
            }
        }

        return $fieldValue;
    }

    protected function shiftDate($shiftDays, $filledFieldType, $shiftUnit)
    {

    }

    /**
     * Get data to fill
     *
     * @param  array $fields
     * @param  \Core\Orm\Entity $entity
     *
     * @return array
     */
    protected function getDataToFill(\Core\Orm\Entity $entity, array $fields)
    {
        $data = array();

        if (empty($fields)) {
            return $data;
        }

        foreach ($fields as $fieldName => $fieldParams) {

            $isSave = false;
            if ($entity->hasRelation($fieldName)) { //relation

                $fieldValue = $this->getValue($fieldName, $entity);
                $isSave = true;

            } else if ($entity->hasField($fieldName)) { //field

                $fieldValue = $this->getValue($fieldName, $entity);
                $isSave = true;

            }

            if ($isSave) {
                if (is_array($fieldValue) && !\Core\Core\Utils\Util::isSingleArray($fieldValue)) {
                    $data = array_merge($data, $fieldValue);
                } else {
                    $data[$fieldName] = $fieldValue;
                }
            }
        }

        //set default values
        if ($entity->isNew()) {
            $parentEntity = $this->getEntity();

            foreach ($this->defaultFields as $defaultFieldName) {

                 if (!isset($fields[$defaultFieldName])) {

                    $parentFieldValue = Utils::getFieldValue($parentEntity, $defaultFieldName);
                    if (!isset($parentFieldValue)) {
                        continue;
                    }

                    $normalizedFieldName = Utils::normalizeFieldName($entity, $defaultFieldName);
                    if (is_array($normalizedFieldName) && is_array($parentFieldValue)) {
                        if (!\Core\Core\Utils\Util::isSingleArray($parentFieldValue)) {
                            $data = array_merge($data, $parentFieldValue);
                            continue;
                        }
                    }

                    $data[$normalizedFieldName] = $parentFieldValue;
                }
            }
        }
        //END: set default values

        return $data;
    }
}