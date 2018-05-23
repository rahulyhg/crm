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

namespace Core\Modules\Advanced\Services;

use \Core\ORM\Entity;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Forbidden;

class MailChimp extends \Core\Services\Record
{
    private $mailChimpManager;
    private $mailChimpSynchronizer;

    protected function init()
    {
        parent::init();
        $this->addDependency('language');
        $this->addDependency('dateTime');
    }

    protected function getDateTime()
    {
        return $this->getInjection('dateTime');
    }

    protected function getLanguage()
    {
        return $this->getInjection('language');
    }

    protected function getMailChimpManager()
    {
        if (!$this->mailChimpManager) {
            $this->mailChimpManager = new \Core\Modules\Advanced\Core\MailChimp\MailChimpManager($this->getEntityManager(), $this->getMetadata(), $this->getConfig(), $this->getFileManager(), $this->getLanguage(), $this->getDateTime());
        }
        return $this->mailChimpManager;
    }

    protected function getMailChimpSynchronizer()
    {
        if (!$this->mailChimpSynchronizer) {
            $this->mailChimpSynchronizer = new \Core\Modules\Advanced\Core\MailChimp\Synchronizer($this->getEntityManager(), $this->getMetadata(), $this->getMailChimpManager());
        }
        return $this->mailChimpSynchronizer;
    }

    public function getCampaignsByOffset($params)
    {
        return $this->getMailChimpManager()->getCampaignsByOffset($params);
    }

    public function getListsByOffset($params)
    {
        return $this->getMailChimpManager()->getListsByOffset($params);
    }

    public function getListGroups($listId)
    {
        return $this->getMailChimpManager()->getListGroupsTree($listId);
    }

    public function saveRelation($params, $data)
    {
        return $this->getRepository()->saveRelations($data);
    }

    public function loadRelations($campaignId)
    {
        return $this->getRepository()->loadRelations($campaignId);
    }

    public function scheduleAllSync()
    {
        $this->getMailChimpSynchronizer()->scheduleCampaignsSync();
        $this->getMailChimpSynchronizer()->scheduleTargetListsSync();
        $this->cleanUp();
    }

    public function processQueueItems()
    {
        $queueHandler = new \Core\Modules\Advanced\Core\MailChimp\QueueHandler($this->getEntityManager(), $this->getMetadata(), $this->getMailChimpManager());
        $queueHandler->processQueueItems();
    }

    public function createCampaign($data)
    {
        return $this->getMailChimpManager()->createCampaign($data);
    }

    public function createList($data)
    {
        return $this->getMailChimpManager()->createList($data);
    }

    public function getGroupTree($listId)
    {
        return $this->getMailChimpManager()->getListGroupsTree($listId);
    }

    public function scheduleSync($entityType, $id)
    {
        $entity = $this->getEntityManager()->getEntity($entityType, $id);
        if (empty($entity)) {
            throw NotFound();
        }
        $pdo = $this->getEntityManager()->getPDO();

        $sql = "UPDATE mail_chimp_manual_sync
            SET completed=1
            WHERE completed=0 AND
                parent_type=" . $pdo->quote($entityType) . " AND
                parent_id=" . $pdo->quote($id);

        $pdo->query($sql);

        $jobIds = [];

        $job = $this->getRepository()->addSyncJob($entityType, $id, true);
        $jobIds[] = $job->id;
        if ($entityType == 'Campaign') {
            $entity->loadLinkMultipleField('targetLists');
            $targetListsIds = $entity->get('targetListsIds');
            foreach ($targetListsIds as $targetListId) {
                $job = $this->getRepository()->addSyncJob('TargetList', $targetListId);
                $jobIds[] = $job->id;
            }
        }
        if (!empty($jobIds)) {
            $manSyncEntity = $this->getEntityManager()->getEntity('MailChimpManualSync');
            $manSyncEntity->set('assignedUserId', $this->getUser()->id);
            $manSyncEntity->set('parentType', $entityType);
            $manSyncEntity->set('parentId', $id);
            $manSyncEntity->set('jobs', json_encode($jobIds));
            $this->getEntityManager()->saveEntity($manSyncEntity);

            $entity->set('mailChimpManualSyncRun' , true);
            $this->getEntityManager()->saveEntity($entity);
        }
        return [];
    }

    public function updateMCListRecipients($data)
    {
        $targetListId = (isset($data['targetListId'])) ? $data['targetListId'] : '';
        if ($targetListId) {
            $targetList = $this->getEntityManager()->getEntity('TargetList', $targetListId);
            if ($targetList) {
                $byUserRequest = !empty($data['byUserRequest']);
                return $this->getMailChimpSynchronizer()->updateMCRecipients($targetList, $byUserRequest);
            }
        }
    }

    public function updateCampaignLogFromMailChimp($data)
    {
        try {
            $campaignId = (isset($data['campaignId'])) ? $data['campaignId'] : '';
            if ($campaignId) {
                $campaign = $this->getEntityManager()->getEntity('Campaign', $campaignId);
                if ($campaign) {
                    $byUserRequest = !empty($data['byUserRequest']);
                    return $this->getMailChimpSynchronizer()->loadLogDataForCampaign($campaign, $byUserRequest);
                }
            }
        } catch (\Exception $e) {
            $GLOBALS['log']->error('MailChimp (updateCampaignLogFromMailChimp) : ' . $e->getMessage());
            return false;
        }
        return true;
    }

    protected function cleanUp()
    {
        $pdo = $this->getEntityManager()->getPDO();
        $query = "DELETE FROM `mail_chimp_batch` WHERE DATE(modified_at) < '".$this->getCleanupFromDate()."' AND deleted = '1'";

        $pdo->query($query);

        $query = "DELETE FROM `mail_chimp_queue` WHERE DATE(modified_at) < '".$this->getCleanupFromDate()."' AND status <> 'Pending'";

        $pdo->query($query);

        $query = "DELETE FROM `mail_chimp_log_marker` WHERE deleted = '1'";

        $pdo->query($query);
    }

    private function getCleanupFromDate()
    {
        $daysForSave = '20';
        $format = 'Y-m-d';

        $datetime = new \DateTime();
        $datetime->modify('-' . $daysForSave . ' days');
        return $datetime->format($format);
    }

}
