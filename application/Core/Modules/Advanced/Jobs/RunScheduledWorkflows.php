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

namespace Core\Modules\Advanced\Jobs;

use \Core\Core\Exceptions;

class RunScheduledWorkflows extends \Core\Core\Jobs\Base
{
    protected $serviceMethodName = 'runScheduledWorkflow';

    public function run()
    {
        $entityManager = $this->getEntityManager();

        $collection = $entityManager->getRepository('Workflow')->where(array('type' => 'scheduled', 'is_active' => true))->find();
        foreach ($collection as $entity) {
            $cronExpression = \Cron\CronExpression::factory($entity->get('scheduling'));

            try {
                $executionTime = $cronExpression->getPreviousRunDate()->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $GLOBALS['log']->error('RunScheduledWorkflows: Workflow ['.$entity->get('id').']: Impossible scheduling expression ['.$entity->get('scheduling').'].');
                continue;
            }

            if ($entity->get('lastRun') == $executionTime) {
                continue;
            }

            $jobData = array(
                'workflowId' => $entity->get('id'),
            );

            if (!$this->isJobExists($jobData, $executionTime)) {
                if ($this->createJob($jobData, $executionTime)) {
                    $entity->set('lastRun', $executionTime);
                    $entityManager->saveEntity($entity);
                }
            }
        }
    }

    /**
     * Create a job for scheduled workflow
     *
     * @param  array  $jobData
     * @param  string $executionTime
     *
     * @return boolean
     */
    protected function createJob(array $jobData, $executionTime)
    {
        $job = $this->getEntityManager()->getEntity('Job');
        $job->set(array(
            'serviceName' => 'Workflow',
            'method' => $this->serviceMethodName,
            'data' => $jobData,
            'executeTime' => $executionTime,
        ));

        if ($this->getEntityManager()->saveEntity($job)) {
            return true;
        }

        return false;
    }

    /**
     * Check if scheduled workflow job is not created
     *
     * @param  array   $jobData
     * @param  string  $time
     *
     * @return boolean
     */
    protected function isJobExists(array $jobData, $time)
    {
        $dateObj = new \DateTime($time);
        $timeWithoutSeconds = $dateObj->format('Y-m-d H:i:');

        $query = "SELECT * FROM job WHERE
                    service_name = 'Workflow'
                    AND method = '".$this->serviceMethodName."'
                    AND execute_time LIKE '".$timeWithoutSeconds."%'
                    AND data = '".json_encode($jobData)."'
                    AND deleted = 0
                    LIMIT 1";

        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($query);
        $sth->execute();

        $scheduledJob = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return empty($scheduledJob) ? false : true;
    }
}
