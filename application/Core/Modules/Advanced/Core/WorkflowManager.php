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

namespace Core\Modules\Advanced\Core;

use Core\Core\Utils\Json;
use Core\Core\Utils\Util;

class WorkflowManager
{
    private $container;

    private $conditionManager;

    private $actionManager;

    private $data;

    protected $cacheFile = 'data/cache/advanced/workflows.php';

    const AFTER_RECORD_SAVED = 'afterRecordSaved';
    const AFTER_RECORD_CREATED = 'afterRecordCreated';

    protected $entityListToIgnore = array();

    public function __construct(\Core\Core\Container $container)
    {
        $this->container = $container;
        $this->conditionManager = new Workflow\ConditionManager($this->container);
        $this->actionManager = new Workflow\ActionManager($this->container);

        $this->entityListToIgnore = $this->container->get('metadata')->get('entityDefs.Workflow.entityListToIgnore');
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getConditionManager()
    {
        return $this->conditionManager;
    }

    protected function getActionManager()
    {
        return $this->actionManager;
    }

    protected function getConfig()
    {
        return $this->container->get('config');
    }

    protected function getUser()
    {
        return $this->container->get('user');
    }

    protected function getEntityManager()
    {
        return $this->container->get('entityManager');
    }

    protected function getFileManager()
    {
        return $this->container->get('fileManager');
    }

    protected function getData($entityName = null, $workflowType = null, $returns = null)
    {
        if (!isset($this->data)) {
            $this->loadWorkflows();
        }

        if (isset($entityName) && isset($workflowType)) {
            if (isset($this->data[$workflowType] [$entityName])) {
                return $this->data[$workflowType] [$entityName];
            } else {
                return $returns;
            }
        }

        if (isset($workflowType)) {
            if (isset($this->data[$workflowType])) {
                return $this->data[$workflowType];
            } else {
                return $returns;
            }
        }

        if (isset($entityName)) {
            return $returns;
        }

        return $this->data;
    }

    /**
     * Run workflow rule
     *
     * @param  \Core\Orm\Entity $entity
     * @param  string           $workflowType
     * @return void
     */
    public function process(\Core\Orm\Entity $entity, $workflowType)
    {
        $entityName = $entity->getEntityType();
        if (in_array($entityName, $this->entityListToIgnore)) {
            return;
        }

        $data = $this->getData($entityName, $workflowType);

        if (isset($data) && is_array($data)) {

            $GLOBALS['log']->debug('WorkflowManager: Start workflow ['.$workflowType.'] for Entity ['.$entityName.', '.$entity->id.'].');

            $conditionManager = $this->getConditionManager();
            $actionManager = $this->getActionManager();

            foreach ($data as $workflowId => $workflowData) {
                $GLOBALS['log']->debug('WorkflowManager: Start workflow rule ['.$workflowId.'].');

                if ($workflowData['portalOnly']) {
                    if (!$this->getUser()->get('portalId')) {
                        continue;
                    }
                    if (!empty($workflowData['portalId'])) {
                        if ($this->getUser()->get('portalId') !== $workflowData['portalId']) {
                            continue;
                        }
                    }
                }

                $conditionManager->setInitData($workflowId, $entity);

                $result = true;
                if (isset($workflowData['conditionsAll'])) {
                    $result &= $conditionManager->checkConditionsAll($workflowData['conditionsAll']);
                }
                if (isset($workflowData['conditionsAny'])) {
                    $result &= $conditionManager->checkConditionsAny($workflowData['conditionsAny']);
                }

                if (isset($workflowData['conditionsFormula']) && !empty($workflowData['conditionsFormula'])) {
                    $result &= $conditionManager->checkConditionsFormula($workflowData['conditionsFormula']);
                }

                $GLOBALS['log']->debug('WorkflowManager: Condition result ['.(bool) $result.'] for workflow rule ['.$workflowId.'].');

                if ($result) {
                    $workflowLogRecord = $this->getEntityManager()->getEntity('WorkflowLogRecord');
                    $workflowLogRecord->set(array(
                        'workflowId' => $workflowId,
                        'targetId' => $entity->id,
                        'targetType' => $entity->getEntityType()
                    ));
                    $this->getEntityManager()->saveEntity($workflowLogRecord);
                }

                if ($result && isset($workflowData['actions'])) {
                    $GLOBALS['log']->debug('WorkflowManager: Start running Actions for workflow rule ['.$workflowId.'].');

                    $actionManager->setInitData($workflowId, $entity);

                    try {
                        $actionResult = $actionManager->runActions($workflowData['actions']);
                    } catch (\Exception $e) {
                        $GLOBALS['log']->notice('Workflow: failed action execution for workflow [' . $workflowId . ']. Details: '. $e->getMessage());
                    }

                    $GLOBALS['log']->debug('WorkflowManager: End running Actions for workflow rule ['.$workflowId.'].');
                }

                $GLOBALS['log']->debug('WorkflowManager: End workflow rule ['.$workflowId.'].');
            }

            $GLOBALS['log']->debug('WorkflowManager: End workflow ['.$workflowType.'] for Entity ['.$entityName.', '.$entity->id.'].');
        }
    }

    public function checkConditions($workflow, $entity)
    {
        $result = true;

        $conditionsAll = $workflow->get('conditionsAll');
        $conditionsAny = $workflow->get('conditionsAny');

        $conditionsAll = JSON::decode(JSON::encode($conditionsAll), true);
        $conditionsAny = JSON::decode(JSON::encode($conditionsAny), true);

        $conditionsFormula = $workflow->get('conditionsFormula');

        $conditionManager = $this->getConditionManager();
        $conditionManager->setInitData($workflow->id, $entity);

        if (isset($conditionsAll)) {
            $result &= $conditionManager->checkConditionsAll($conditionsAll);
        }
        if (isset($conditionsAny)) {
            $result &= $conditionManager->checkConditionsAny($conditionsAny);
        }

        if ($conditionsFormula && $conditionsFormula !== '') {
            $result &= $conditionManager->checkConditionsFormula($conditionsFormula);
        }

        return $result;
    }

    public function runActions($workflow, $entity)
    {
        $actions = $workflow->get('actions');
        $actions = JSON::decode(JSON::encode($actions), true);

        $actionManager = $this->getActionManager();
        $actionManager->setInitData($workflow->id, $entity);

        $actionManager->runActions($actions);
    }

    /**
     * Load workflows
     *
     * @return void
     */
    public function loadWorkflows($reload = false)
    {
        if (!$reload && $this->getConfig()->get('useCache') && file_exists($this->cacheFile)) {
            $this->data = $this->getFileManager()->getPhpContents($this->cacheFile);
            return;
        }

        $this->data = $this->getWorkflowData();

        if ($this->getConfig()->get('useCache')) {
            $this->getFileManager()->putPhpContents($this->cacheFile, $this->data);
        }
    }

    /**
     * Get all workflows from database and save into cache
     *
     * @return array
     */
    protected function getWorkflowData()
    {
        $requiredFields = array(
            'conditions_all',
            'conditions_any',
            'conditions_formula',
            'actions',
        );

        $data = array();

        $pdo = $this->getEntityManager()->getPDO();

        $sql = "SELECT * FROM workflow WHERE is_active = 1 AND deleted = 0";
        $sth = $pdo->prepare($sql);
        $sth->execute();

        $records = $sth->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($records as $row) {
            $rowData = array();
            foreach ($requiredFields as $fieldName) {
                if (isset($row[$fieldName])) {
                    $ccFieldName = Util::toCamelCase($fieldName);
                    $fieldValue = $row[$fieldName];
                    if (Json::isJSON($fieldValue)) {
                        $fieldValue = Json::decode($fieldValue, true);
                    }
                    if (!empty($fieldValue)) {
                        $rowData[$ccFieldName] = $fieldValue;
                    }
                }
            }
            $rowData['portalOnly'] = (bool) $row['portal_only'];
            if ($rowData['portalOnly']) {
                $rowData['portalId'] = $row['portal_id'];
            }

            $data[$row['type']] [$row['entity_type']] [$row['id']] = $rowData;
        }

        return $data;
    }
}