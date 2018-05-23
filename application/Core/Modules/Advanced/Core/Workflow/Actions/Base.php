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

namespace Core\Modules\Advanced\Core\Workflow\Actions;

use Core\Core\Exceptions\Error;
use Core\Modules\Advanced\Core\Workflow\Utils;

use Core\ORM\Entity;

abstract class Base
{
    private $container;

    private $entityManager;

    private $workflowId;

    protected $entity;

    protected $action;

    public function __construct(\Core\Core\Container $container)
    {
        $this->container = $container;
        $this->entityManager = $container->get('entityManager');
    }

    protected function getContainer()
    {
        return $this->container;
    }

    public function setWorkflowId($workflowId)
    {
        $this->workflowId = $workflowId;
    }

    protected function getWorkflowId()
    {
        return $this->workflowId;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getServiceFactory()
    {
        return $this->container->get('serviceFactory');
    }

    protected function getMetadata()
    {
        return $this->container->get('metadata');
    }

    protected function getFormulaManager()
    {
        return $this->container->get('formulaManager');
    }

    protected function getUser()
    {
        return $this->container->get('user');
    }

    protected function getEntity()
    {
        return $this->entity;
    }

    protected function getActionData()
    {
        return $this->action;
    }

    protected function getHelper()
    {
        return $this->container->get('workflowHelper');
    }

    protected function getEntityHelper()
    {
        return $this->getHelper()->getEntityHelper();
    }

    protected function getFormulaVariables($entity)
    {
        $o = (object) [];
        $o->targetEntity = $entity;

        return $o;
    }

    public function process($entity, $action)
    {
        $this->entity = $entity;
        $this->action = $action;

        $GLOBALS['log']->debug('Workflow\Actions: Start ['.$action['type'].'] with cid ['.$action['cid'].'] for entity ['.$entity->getEntityType().', '.$entity->id.'].');

        $result = $this->run($entity, $action);

        $GLOBALS['log']->debug('Workflow\Actions: End ['.$action['type'].'] with cid ['.$action['cid'].'] for entity ['.$entity->getEntityType().', '.$entity->id.'], result ['.(bool) $result.'].');

        if (!$result) {
            throw new Error('Workflow['.$this->getWorkflowId().']: Action failed [' . $action['type'] . '] with cid [' . $action['cid'] . '].');
        }
    }

    /**
     * Get execute time defined in workflow
     *
     * @return string
     */
    protected function getExecuteTime($data)
    {
        $execution = $data['execution'];

        $executeTime = date('Y-m-d H:i:s');

        switch ($execution['type']) {
            case 'immediately':
                return $executeTime;
                break;

            case 'later':
                if (!empty($execution['field'])) {
                   $executeTime =  Utils::getFieldValue($this->getEntity(), $execution['field']);
                }
                if (!empty($execution['shiftDays'])) {
                    $shiftUnit = 'days';
                    if (!empty($execution['shiftUnit'])) {
                        $shiftUnit = $execution['shiftUnit'];
                    }
                    if (!in_array($shiftUnit, ['hours', 'minutes', 'days', 'months'])) {
                        $shiftUnit = 'days';
                    }
                    $executeTime = Utils::shiftDays($execution['shiftDays'], $executeTime, 'datetime', $shiftUnit);
                }
                break;

            default:
                throw new Error('Workflow['.$this->getWorkflowId().']: Unknown execution type [' . $execution['type'] . ']');
                break;
        }

        return $executeTime;
    }

    abstract protected function run(Entity $entity, array $actionData);
}