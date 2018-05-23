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

use Core\Core\Exceptions\Error;

class ConditionManager extends BaseManager
{
    protected $dirName = 'Conditions';

    protected $requiredOptions = array(
        'comparison',
        'fieldToCompare',
    );

    protected function getFormulaManager()
    {
        return $this->getContainer()->get('formulaManager');
    }

    /**
     * Check conditions "Any"
     *
     * @param  array  $conditions
     * @return bool
     */
    public function checkConditionsAny(array $conditions)
    {
        if (!isset($conditions) || empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            if ($this->processCheck($condition)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check conditions "Any"
     *
     * @param  array  $conditions
     * @return bool
     */
    public function checkConditionsAll(array $conditions)
    {
        if (!isset($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (!$this->processCheck($condition)) {
                return false;
            }
        }

        return true;
    }

    public function checkConditionsFormula($formula)
    {
        if (!isset($formula) || empty($formula)) {
            return true;
        }
        $o = (object) [];
        $o->targetEntity = $this->getEntity();

        return $this->getFormulaManager()->run($formula, $this->getEntity(), $o);
    }

    /**
     * Compare a single condition
     *
     * @param  Entity $entity
     * @param  array $conditions
     * @return bool
     */
    protected function processCheck(array $condition)
    {
        $entity = $this->getEntity();
        $entityName = $entity->getEntityName();

        if (!$this->validate($condition)) {
            $GLOBALS['log']->warning('Workflow['.$this->getWorkflowId().']: Condition data is broken for the Entity ['.$entityName.'].');
            return false;
        }

        $compareClass = $this->getClass($condition['comparison']);
        if (isset($compareClass)) {
            return $compareClass->process($entity, $condition);
        }

        return false;
    }
}