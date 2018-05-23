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

namespace Core\Modules\Advanced\Repositories;

use \Core\ORM\EntityManager;
use \Core\ORM\EntityFactory;

class MailChimp extends \Core\Core\ORM\Repositories\RDB
{

    protected $campaignFields = array(
        'mailChimpCampaignId',
        'mailChimpCampaignName',
        'mailChimpCampaignWebId',
        'mailChimpCampaignStatus',
    );

    protected $targetListFields = array(
        'mailChimpListId',
        'mailChimpListName',
        'mcListGroupingId',
        'mcListGroupId',
        'mcListGroupingName',
        'mcListGroupName',
    );

    public function __construct($entityType, EntityManager $entityManager, EntityFactory $entityFactory)
    {
        $this->entityType = $entityType;
        $this->entityFactory = $entityFactory;
        $this->entityManager = $entityManager;
        $this->init();
    }

    protected function init()
    {
        parent::init();

        $this->addDependency('acl');
        $this->addDependency('user');
    }

    protected function getMapper()
    {
        if (empty($this->mapper)) {
            $this->mapper = $this->getEntityManager()->getMapper('RDB');
        }
        return $this->mapper;
    }

    protected function getAcl()
    {
        return $this->getInjection('acl');
    }

    protected function getUser()
    {
        return $this->getInjection('user');
    }

    protected function getPDO()
    {
        return $this->getEntityManager()->getPDO();
    }

    public function saveRelations($data)
    {
        switch ($data['foreignEntity']) {
            case "Campaign": return $this->saveCampaignRelations($data);
            case "TargetList": return $this->saveTargetListRelations($data);
        }
        return false;
    }

    private function saveCampaignRelations($data)
    {
        $idsUpdated = array();
        $campaignId = $data['id'];
        $entity = $this->getEntityManager()->getEntity('Campaign', $campaignId);
        if (!empty($entity)) {
            if ($this->getAcl()->check($entity, 'edit')) {
                foreach($this->campaignFields as $fieldName) {
                    if ($entity->hasField($fieldName)){
                        $entity->set($fieldName, $data[$fieldName]);
                    }
                }
                if ($this->getEntityManager()->saveEntity($entity)) {
                    $idsUpdated[] = $campaignId;
                }
            }
        }

        foreach ($data as $name => $value) {
            if (strpos($name, '_') !== false) {
                list($id, $field) = explode('_', $name);
                $entity = $this->getEntityManager()->getEntity('TargetList', $id);
                if (isset($entity->id) && $entity->id && in_array($field, $this->targetListFields) && $entity->get($field) != $value) {
                    if ($this->getAcl()->check($entity, 'edit')) {
                        $entity->set($field, $value);
                        if ($this->getEntityManager()->saveEntity($entity) && !in_array($id, $idsUpdated)) {
                            $idsUpdated[] = $id;
                        }
                    }
                }
            }
        }

        return $idsUpdated;
    }

    private function saveTargetListRelations($data)
    {
        $idsUpdated = array();
        $targetListId = $data['id'];
        $entity = $this->getEntityManager()->getEntity('TargetList', $targetListId);

        if (!empty($entity)) {
            if ($this->getAcl()->check($entity, 'edit')) {
                foreach($this->targetListFields as $fieldName) {
                    if ($entity->hasField($fieldName)){
                        $entity->set($fieldName, $data[$fieldName]);
                    }
                }
                if ($this->getEntityManager()->saveEntity($entity)) {
                    $idsUpdated[] = $targetListId;
                }
            }
        }
        return $idsUpdated;
    }

    public function loadRelations($id)
    {
        $result = array(
            'id' => $id,
            'scope' => 'MailChimp',
            'syncIsRunning' => false
        );

        $campaign = $this->getEntityManager()->getEntity('Campaign', $id);
        if (!empty($campaign)) {
            if ($this->getAcl()->check($campaign, 'read')) {
                foreach ($this->campaignFields as $fieldName) {
                    $result[$fieldName] = $campaign->get($fieldName);
                }
                $result['syncIsRunning'] = $campaign->get('mailChimpManualSyncRun');
            } else {
                throw new Forbidden();
            }

            $campaign->loadLinkMultipleField('targetLists');

            $targetListsIds = $campaign->get('targetListsIds');
            $result['targetListsIds'] = array();
            foreach ($targetListsIds as $targetListId) {
                $targetList = $this->getEntityManager()->getEntity('TargetList', $targetListId);

                if (!empty($targetList)) {
                    if ($this->getAcl()->check($targetList, 'read')) {
                        $targetListData = array();
                        $targetListData['id'] = $targetList->id;
                        $targetListData['name'] = $targetList->get('name');

                        foreach ($this->targetListFields as $fieldName) {
                            $targetListData[$fieldName] = $targetList->get($fieldName);
                        }

                        if (!empty($targetListData['mcListGroupingId']) && empty($targetListData['mcListGroupId'])) {
                            $targetListData['mcListGroupId'] = $targetListData['mcListGroupingId'];
                            $targetListData['mcListGroupName'] = $targetListData['mcListGroupingName'];
                            $targetListData['mcListGroupingId'] = '';
                            $targetListData['mcListGroupingName'] = '';
                        }
                        foreach($targetListData as $field => $value) {
                            $result[$targetList->id . '_' . $field] = $value;
                        }
                        $result['targetListsIds'][] = $targetListId;
                    }
                }
            }
        } else {
            $targetList = $this->getEntityManager()->getEntity('TargetList', $id);
            if (!empty($targetList)) {
                if ($this->getAcl()->check($targetList, 'read')) {

                    foreach ($this->targetListFields as $fieldName) {
                        $result[$fieldName] = $targetList->get($fieldName);
                    }
                    $result['syncIsRunning'] = $targetList->get('syncIsRunning');

                    if (!empty($result['mcListGroupingId']) && empty($result['mcListGroupId'])) {
                        $result['mcListGroupId'] = $result['mcListGroupingId'];
                        $result['mcListGroupName'] = $result['mcListGroupingName'];
                        $result['mcListGroupingId'] = '';
                        $result['mcListGroupingName'] = '';
                    }

                } else {
                    throw new Forbidden();
                }
            }
        }
        return $result;
    }

    public function checkManualSyncs()
    {
        $currentUser = $this->getUser();
        $pdo = $this->getEntityManager()->getPDO();
        $result = array();

        $activeSyncs = $this->getEntityManager()->getRepository('MailChimpManualSync')
            ->where(array(
                'assignedUserId' => $currentUser->id,
                'completed' => false
            ))
            ->find(array(
                'orderBy' => 'createdAt'
            ));

        if ($activeSyncs) {
            foreach ($activeSyncs as $sync) {

                $jobIds = $sync->get('jobs');
                $completed = true;
                $failed = false;
                $executeTime = '';

                foreach ($jobIds as $jobId) {
                    $job = $this->getEntityManager()->getEntity('Job', $jobId);
                    if (!empty($job)) {
                        $status = $job->get('status');

                        if (in_array($status, array('Pending', 'Running'))) {
                            $completed = false;
                            break;
                        }

                        if (!$failed && $status == "Failed") {
                            $failed = true;
                        }

                        $executeTime = ($job->get('executeTime') > $executeTime) ? $job->get('executeTime') : $executeTime;

                        $batchNotFinishedCount = $this->getEntityManager()->getRepository('MailChimpQueue')
                            ->where([
                                'status' => ['Pending', 'Running', 'Sent'],
                                'parentId' => $job->get('targetId'),
                                'parentType' => $job->get('targetType'),
                                'createdAt>' => $job->get('executeTime')
                            ])
                            ->count();

                        if ($batchNotFinishedCount) {
                            $completed = false;
                            break;
                        }

                        $batchFailedCount = $this->getEntityManager()->getRepository('MailChimpQueue')
                            ->where([
                                'status' => ['Failed'],
                                'parentId' => $job->get('targetId'),
                                'parentType' => $job->get('targetType'),
                                'createdAt>' => $job->get('executeTime')
                            ])
                            ->count();

                        if ($batchFailedCount) {
                            $failed = false;
                            break;
                        }

                        $lastUpdated = $this->getEntityManager()->getRepository('MailChimpQueue')
                            ->where([
                                'status' => ['Success'],
                                'parentId' => $job->get('targetId'),
                                'parentType' => $job->get('targetType'),
                                'createdAt>' => $job->get('executeTime')
                            ])
                            ->max('modifiedAt');

                        $executeTime = max($job->get('executeTime'), $executeTime, $lastUpdated);
                    }
                }

                if ($completed) {
                    $sync->set('completed', true);
                    $this->getEntityManager()->saveEntity($sync);
                    $parent = $this->getEntityManager()->getEntity($sync->get('parentType'), $sync->get('parentId'));
                    $data = null;
                    if (!empty($parent)) {
                        if ($executeTime) {
                            $parent->set('mailChimpLastSuccessfulUpdating', $executeTime);
                        }
                        $parent->set('mailChimpManualSyncRun', false);
                        $this->getEntityManager()->saveEntity($parent);

                        $data = array(
                            'entityType' => $parent->getEntityType(),
                            'entityName' => $parent->get('name'),
                            'id' => $parent->id,
                            'lastSynced' => $executeTime,
                            'failed' => $failed
                        );
                    }
                    $result[] = array(
                        'id' => $sync->id,
                        'data' => $data
                    );
                }
            }
        }
        return $result;
    }

    public function addSyncJob($entityType, $entityId, $byUserRequest = false)
    {
        switch ($entityType) {
            case 'Campaign':
                $methodName = 'updateCampaignLogFromMailChimp';
                $fieldName = 'campaignId';
                break;
            case 'TargetList':
                $methodName = 'updateMCListRecipients';
                $fieldName = 'targetListId';
                break;
            default: throw \Error('Unknown entity type in scheduling MailChimp Sync');
        }

        $job = $this->getEntityManager()->getRepository('Job')
                ->where([
                    'method' => $methodName,
                    'status' => ['Pending', 'Running'],
                    'serviceName' => 'MailChimp',
                    'targetId' => $entityId
                ])
                ->order('executeTime', 'DESC')
                ->findOne();

        if (!$job) {
            $now = new \DateTime("NOW", new \DateTimeZone('UTC'));
            $job = $this->getEntityManager()->getEntity('Job');
            $job->set(array(
                    'method' => $methodName,
                    'serviceName' => 'MailChimp',
                    'executeTime' => $now->format("Y-m-d H:i" . ":00"),
                    'data' => json_encode([$fieldName => $entityId, 'byUserRequest' => $byUserRequest]),
                    'targetId' => $entityId,
                    'targetType' => $entityType
                )
            );
            $this->getEntityManager()->saveEntity($job);
        }
        return $job;
    }
}
