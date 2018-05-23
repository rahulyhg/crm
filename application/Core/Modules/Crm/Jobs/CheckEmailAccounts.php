<?php


namespace Core\Modules\Crm\Jobs;

use \Core\Core\Exceptions\Error;

class CheckEmailAccounts extends \Core\Core\Jobs\Base
{
    public function run($data, $targetId)
    {
        if (!$targetId) {
            throw new Error();
        }

        $service = $this->getServiceFactory()->create('EmailAccount');
        $entity = $this->getEntityManager()->getEntity('EmailAccount', $targetId);

        if (!$entity) {
            throw new Error("Job CheckEmailAccounts '".$targetId."': EmailAccount does not exist.");
        };

        if ($entity->get('status') !== 'Active') {
            throw new Error("Job CheckEmailAccounts '".$targetId."': EmailAccount is not active.");
        }

        try {
            $service->fetchFromMailServer($entity);
        } catch (\Exception $e) {
            throw new Error('Job CheckEmailAccounts '.$entity->id.': [' . $e->getCode() . '] ' .$e->getMessage());
        }
        return true;
    }

    public function prepare($data, $executeTime)
    {
        $collection = $this->getEntityManager()->getRepository('EmailAccount')->where(array(
            'status' => 'Active'
        ))->find();
        foreach ($collection as $entity) {
            $running = $this->getEntityManager()->getRepository('Job')->where(array(
                'scheduledJobId' => $data['id'],
                'status' => 'Running',
                'targetType' => 'EmailAccount',
                'targetId' => $entity->id
            ))->findOne();
            if ($running) continue;

            $countPending = $this->getEntityManager()->getRepository('Job')->where(array(
                'scheduledJobId' => $data['id'],
                'status' => 'Pending',
                'targetType' => 'EmailAccount',
                'targetId' => $entity->id
            ))->count();
            if ($countPending > 1) continue;

            $job = $this->getEntityManager()->getEntity('Job');

            $jobEntity = $this->getEntityManager()->getEntity('Job');
            $jobEntity->set(array(
                'name' => $data['name'],
                'scheduledJobId' => $data['id'],
                'executeTime' => $executeTime,
                'method' => 'CheckEmailAccounts',
                'targetType' => 'EmailAccount',
                'targetId' => $entity->id
            ));
            $this->getEntityManager()->saveEntity($jobEntity);
        }

        return true;
    }
}

