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

namespace Core\Modules\Advanced\Core\Workflow;

use Core\Core\Exceptions\Error;

abstract class BaseManager
{
    protected $dirName;

    private $container;

    private $processId;

    private $entityList;

    private $workflowIdList;

    private $objects;

    /**
     * Required option in condition/action data
     * @var array
     */
    protected $requiredOptions = array();

    public function __construct(\Core\Core\Container $container)
    {
        $this->container = $container;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    public function setInitData($workflowId, \Core\Orm\Entity $entity)
    {
        $this->processId = $workflowId . '-'. $entity->id;

        $this->workflowIdList[$this->processId] = $workflowId;
        $this->entityList[$this->processId] = $entity;
    }

    protected function getProcessId()
    {
        if (empty($this->processId)) {
            throw new Error('Workflow['.__CLASS__.'], getProcessId(): Empty processId.');
        }

        return $this->processId;
    }

    protected function getWorkflowId($processId = null)
    {
        if (!isset($processId)) {
            $processId = $this->getProcessId();
        }

        if (empty($this->workflowIdList[$processId])) {
            throw new Error('Workflow['.__CLASS__.'], getWorkflowId(): Empty workflowId.');
        }

        return $this->workflowIdList[$processId];
    }

    protected function getEntity($processId = null)
    {
        if (!isset($processId)) {
            $processId = $this->getProcessId();
        }

        if (empty($this->entityList[$processId])) {
            throw new Error('Workflow['.__CLASS__.'], getEntity(): Empty Entity object.');
        }

        return $this->entityList[$processId];
    }

    /**
     * Get class by $name
     *
     * @param  string $name
     * @return object
     */
    protected function getClass($name, $processId = null)
    {
        $name = ucfirst($name);
        $name = str_replace("\\", "", $name);

        if (!isset($processId)) {
            $processId = $this->getProcessId();
        }

        $workflowId = $this->getWorkflowId($processId);

        if (!isset($this->objects[$processId][$name])) {
            $className = '\Core\Custom\Modules\Advanced\Core\Workflow\\' . ucfirst($this->dirName) . '\\' . $name;
            if (!class_exists($className)) {
                $className .=  'Type';
                if (!class_exists($className)) {

                    $className = '\Core\Modules\Advanced\Core\Workflow\\' . ucfirst($this->dirName) . '\\' . $name;
                    if (!class_exists($className)) {
                        $className .=  'Type';
                        if (!class_exists($className)) {
                            throw new Error('Workflow['.$workflowId.']: Class ['.$className.'] does not exist.');
                        }
                    }

                }
            }

            $class = new $className($this->getContainer());
            $this->objects[$processId][$name] = $class;
        }

        $this->objects[$processId][$name]->setWorkflowId($workflowId);

        return $this->objects[$processId][$name];
    }

    /**
     * Validate condition/action data
     *
     * @param  array $options
     * @return bool
     */
    protected function validate($options)
    {
        foreach ($this->requiredOptions as $optionName) {
            if (!array_key_exists($optionName, $options)) {
                return false;
            }
        }

        return true;
    }
}