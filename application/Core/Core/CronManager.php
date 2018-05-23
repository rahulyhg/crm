<?php


namespace Core\Core;
use \PDO;
use Core\Core\Utils\Json;
use Core\Core\Exceptions\NotFound;

class CronManager
{
    private $container;

    private $config;

    private $fileManager;

    private $entityManager;

    private $scheduledJobUtil;

    const PENDING = 'Pending';

    const RUNNING = 'Running';

    const SUCCESS = 'Success';

    const FAILED = 'Failed';

    protected $lastRunTime = 'data/cache/application/cronLastRunTime.php';

    public function __construct(\Core\Core\Container $container)
    {
        $this->container = $container;

        $this->config = $this->container->get('config');
        $this->fileManager = $this->container->get('fileManager');
        $this->entityManager = $this->container->get('entityManager');
        $this->serviceFactory = $this->container->get('serviceFactory');

        $this->scheduledJobUtil = $this->container->get('scheduledJob');
        $this->cronJob = new \Core\Core\Utils\Cron\Job($this->config, $this->entityManager);
        $this->cronScheduledJob = new \Core\Core\Utils\Cron\ScheduledJob($this->config, $this->entityManager);
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getServiceFactory()
    {
        return $this->serviceFactory;
    }

    protected function getScheduledJobUtil()
    {
        return $this->scheduledJobUtil;
    }

    protected function getCronJob()
    {
        return $this->cronJob;
    }

    protected function getCronScheduledJob()
    {
        return $this->cronScheduledJob;
    }

    protected function getLastRunTime()
    {
        $lastRunData = $this->getFileManager()->getPhpContents($this->lastRunTime);

        $lastRunTime = time() - intval($this->getConfig()->get('cron.minExecutionTime')) - 1;
        if (is_array($lastRunData) && !empty($lastRunData['time'])) {
            $lastRunTime = $lastRunData['time'];
        }

        return $lastRunTime;
    }

    protected function setLastRunTime($time)
    {
        $data = array(
            'time' => $time,
        );
        return $this->getFileManager()->putPhpContents($this->lastRunTime, $data);
    }

    protected function checkLastRunTime()
    {
        $currentTime = time();
        $lastRunTime = $this->getLastRunTime();
        $minTime = $this->getConfig()->get('cron.minExecutionTime');

        if ($currentTime > ($lastRunTime + $minTime) ) {
            return true;
        }

        return false;
    }

    /**
     * Run Cron
     *
     * @return void
     */
    public function run()
    {
        if (!$this->checkLastRunTime()) {
            $GLOBALS['log']->info('CronManager: Stop cron running, too frequent execution.');
            return;
        }

        $this->setLastRunTime(time());

        $this->getCronJob()->markFailedJobs();
        $this->getCronJob()->updateFailedJobAttempts();
        $this->createJobsFromScheduledJobs();
        $this->getCronJob()->removePendingJobDuplicates();

        $pendingJobList = $this->getCronJob()->getPendingJobList();

        foreach ($pendingJobList as $job) {
            $jobEntity = $this->getEntityManager()->getEntity('Job', $job['id']);

            if (!isset($jobEntity)) {
                $GLOBALS['log']->error('CronManager: empty Job entity ['.$job['id'].'].');
                continue;
            }

            $jobEntity->set('status', self::RUNNING);
            $this->getEntityManager()->saveEntity($jobEntity);

            $isSuccess = true;

            try {
                if (!empty($job['scheduled_job_id'])) {
                    $this->runScheduledJob($job);
                } else {
                    $this->runService($job);
                }
            } catch (\Exception $e) {
                $isSuccess = false;
                $GLOBALS['log']->error('CronManager: Failed job running, job ['.$job['id'].']. Error Details: '.$e->getMessage());
            }

            $status = $isSuccess ? self::SUCCESS : self::FAILED;

            $jobEntity->set('status', $status);
            $this->getEntityManager()->saveEntity($jobEntity);

            if (!empty($job['scheduled_job_id'])) {
                $this->getCronScheduledJob()->addLogRecord($job['scheduled_job_id'], $status, null, $job['target_id'], $job['target_type']);
            }
        }
    }

    /**
     * Run Scheduled Job
     *
     * @param  array  $job
     *
     * @return void
     */
    protected function runScheduledJob(array $job)
    {
        $jobName = $job['method'];

        $className = $this->getScheduledJobUtil()->get($jobName);
        if ($className === false) {
            throw new NotFound();
        }

        $jobClass = new $className($this->container);
        $method = 'run';
        if (!method_exists($jobClass, $method)) {
            throw new NotFound();
        }

        $data = null;
        if (!empty($job['data'])) {
            $data = $job['data'];
            if (Json::isJSON($data)) {
                $data = Json::decode($data, true);
            }
        }

        $jobClass->$method($data, $job['target_id'], $job['target_type']);
    }

    /**
     * Run Service
     *
     * @param  array  $job
     *
     * @return void
     */
    protected function runService(array $job)
    {
        $serviceName = $job['service_name'];

        if (!$this->getServiceFactory()->checkExists($serviceName)) {
            throw new NotFound();
        }

        $service = $this->getServiceFactory()->create($serviceName);
        $serviceMethod = $job['method'];

        if (!method_exists($service, $serviceMethod)) {
            throw new NotFound();
        }

        $data = $job['data'];
        if (Json::isJSON($data)) {
            $data = Json::decode($data, true);
        }

        $service->$serviceMethod($data, $job['target_id'], $job['target_type']);
    }

    /**
     * Check scheduled jobs and create related jobs
     *
     * @return array  List of created Jobs
     */
    protected function createJobsFromScheduledJobs()
    {
        $activeScheduledJobList = $this->getCronScheduledJob()->getActiveScheduledJobList();

        $runningScheduledJobIdList = $this->getCronJob()->getRunningScheduledJobIdList();

        $createdJobIdList = array();
        foreach ($activeScheduledJobList as $scheduledJob) {
            $scheduling = $scheduledJob['scheduling'];

            try {
                $cronExpression = \Cron\CronExpression::factory($scheduling);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('CronManager (ScheduledJob ['.$scheduledJob['id'].']): Scheduling string error - '. $e->getMessage() . '.');
                continue;
            }

            try {
                $nextDate = $cronExpression->getNextRunDate()->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $GLOBALS['log']->error('CronManager (ScheduledJob ['.$scheduledJob['id'].']): Unsupported CRON expression ['.$scheduling.']');
                continue;
            }

            $existingJob = $this->getCronJob()->getJobByScheduledJob($scheduledJob['id'], $nextDate);
            if ($existingJob) continue;

            $className = $this->getScheduledJobUtil()->get($scheduledJob['job']);
            if ($className) {
                if (method_exists($className, 'prepare')) {
                    $implementation = new $className($this->container);
                    $implementation->prepare($scheduledJob, $nextDate);
                    continue;
                }
            }

            if (in_array($scheduledJob['id'], $runningScheduledJobIdList)) {
                continue;
            }

            $jobEntity = $this->getEntityManager()->getEntity('Job');
            $jobEntity->set(array(
                'name' => $scheduledJob['name'],
                'status' => self::PENDING,
                'scheduledJobId' => $scheduledJob['id'],
                'executeTime' => $nextDate,
                'method' => $scheduledJob['job']
            ));
            $this->getEntityManager()->saveEntity($jobEntity);

            $createdJobIdList[] = $jobEntity->id;
        }

        return $createdJobIdList;
    }
}

