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

namespace Core\Modules\Advanced\Core\MailChimp;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\NotFound;
use \Core\ORM\Entity;

class QueueHandler
{
    protected $entityManager = null;
    protected $metadata = null;
    protected $mailChimpManager = null;

    public function __construct($entityManager, $metadata, $mailChimpManager)
    {
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->mailChimpManager = $mailChimpManager;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getMailChimpManager()
    {
        return $this->mailChimpManager;
    }

    public function processQueueItems()
    {
        $this->compactItemsInBatches();
        $this->runOtherItems();
        $this->checkSentBatches();
        $this->sendBatches();
    }

    protected function runOtherItems()
    {
        $mergeFieldsExisting = $this->getEntityManager()->getRepository('MailChimpQueue')
            ->where(['name' => 'CheckMergeFieldsExisting', 'status' => 'Pending'])->findOne();
        if ($mergeFieldsExisting) {
            $mergeFieldsExisting->set('status', 'Running');
            $this->getEntityManager()->saveEntity($mergeFieldsExisting);

            try {
                $synchronizer = new Synchronizer($this->entityManager, $this->metadata, $this->mailChimpManager);
                $synchronizer->checkMergeFieldsExisting();

                $mergeFieldsExisting->set('status', 'Success');
            } catch (\Exception $e) {
                $GLOBALS['log']->error('MailChimp (CheckMergeFieldsExisting) : ' . $e->getMessage());
                $mergeFieldsExisting->set('status', 'Failed');
            }
            $this->getEntityManager()->saveEntity($mergeFieldsExisting);
        }
    }

    protected function checkSentBatches()
    {
        $batchQueue = $this->getEntityManager()->getRepository('MailChimpQueue')
            ->where(['name' => 'Batch', 'status' => 'Sent'])
            ->order('orderNumber')
            ->find();

        if ($batchQueue) {
            $synchronizer = new Synchronizer($this->entityManager, $this->metadata, $this->mailChimpManager);
            foreach ($batchQueue as $batchItem) {
                $batch = $this->getEntityManager()->getRepository('MailChimpBatch')->where(['queueId' => $batchItem->id])->findOne();
                if ($batch) {
                    try {
                        $success = $synchronizer->loadBatchResult($batch);
                        if ($success) {
                            $batchItem->set('status', 'Success');
                            $this->getEntityManager()->saveEntity($batchItem);
                        }
                    } catch (\Exception $e) {
                        $GLOBALS['log']->error('MailChimp (loadBatchResult - batchId ' . $batch->id .') : ' . $e->getMessage());
                        if ($e->getCode() == 404) {
                            $this->getEntityManager()->removeEntity($batch);
                            $batchItem->set('status', 'Failed');

                        } else {
                            $batchItem->set('attempsLeft', $batchItem->get('attempsLeft') - 1);
                            if ($batchItem->get('attempsLeft') > 1) {
                                $batchItem->set('status', 'Failed');
                                $this->getEntityManager()->removeEntity($batch);
                            }
                        }
                        $this->getEntityManager()->saveEntity($batchItem);
                    }
                } else {
                    $batchItem->set('status', 'Failed');
                    $this->getEntityManager()->saveEntity($batchItem);
                }
            }
        }
    }

    protected function sendBatches()
    {
        $batchQueue = $this->getEntityManager()->getRepository('MailChimpQueue')
            ->where(['name' => 'Batch', 'status' => 'Pending'])
            ->order('orderNumber')
            ->find();

        if ($batchQueue) {
            foreach ($batchQueue as $batchItem) {
                $batchItem->set('status', 'Running');
                $this->getEntityManager()->saveEntity($batchItem);
                $operations = $batchItem->get('data');
                if (is_array($operations) && count($operations)) {
                    $batchResultId = $this->getMailChimpManager()->sendBatchRequest($operations);
                    if ($batchResultId) {
                        $batchItem->set('status', 'Sent');
                        $batchRecord = $this->getEntityManager()->getEntity('MailChimpBatch');
                        $batchFields = $batchItem->get('additionalData');
                        if ($batchFields) {
                            $batchFields = (array) $batchFields;
                            $batchRecord->set($batchFields);
                        }
                        $batchRecord->set('name', $batchResultId);
                        $batchRecord->set('queueId', $batchItem->id);
                        $this->getEntityManager()->saveEntity($batchRecord);

                    } else {
                        $batchItem->set('attempsLeft', $batchItem->get('attempsLeft') - 1);
                        if ($batchItem->get('attempsLeft') > 1) {
                            $batchItem->set('status', 'Failed');
                        } else {
                            $batchItem->set('status', 'Pending');
                        }

                    }
                } else {
                    $batchItem->set('status', 'Success');
                }
                $this->getEntityManager()->saveEntity($batchItem);
            }
        }
    }

    protected function compactItemsInBatches()
    {
        $memberQueue = $this->getEntityManager()->getRepository('MailChimpQueue')->where(
                array(
                    'name' => ['Subscribe', 'Unsubscribe','UpdateMember'],
                    'status' => 'Pending'
                ))->find();
        if (count($memberQueue)) {
            $this->getMailChimpManager()->parseQueueItemsMembers($memberQueue);
        }

        $updateListQueue = $this->getEntityManager()->getRepository('MailChimpQueue')->where(
                array(
                    'name' => 'UpdateList',
                    'status' => 'Pending'
                ))->find();
        if (count($updateListQueue)) {
            $this->getMailChimpManager()->parseQueueItemsUpdateList($updateListQueue);
        }
    }

}
