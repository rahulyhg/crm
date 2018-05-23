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

namespace Core\Modules\Advanced\Core\MailChimp;

use Core\ORM\Entity;

class MailChimpManager
{

    const MAX_BATCH_PORTION = 100;

    protected $client = null;

    protected $recipientHelper = null;

    public function __construct($entityManager, $metadata, $config, $fileManager, $language, $dateTime)
    {
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->language = $language;
        $this->dateTime = $dateTime;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getLanguage()
    {
        return $this->language;
    }

    protected function getDateTime()
    {
        return $this->dateTime;
    }

    protected function getClient($hardUpdate = false)
    {
        $client = $this->client;
        if ($hardUpdate || !$client) {
            $factory = new ClientManager($this->getEntityManager(), $this->getMetadata(), $this->getConfig());
            $this->client = $factory->create('MailChimp', null);
        }
        return $this->client;
    }

    protected function getRecipientHelper()
    {
        if (!$this->recipientHelper) {
            $this->recipientHelper = new RecipientHelper($this->getEntityManager(), $this->getMetadata(), $this->getConfig(), $this->getLanguage(), $this->getDateTime());
        }
        return $this->recipientHelper;
    }

    public function getCampaignsByOffset($params)
    {
        if (isset($params['filter']) && $params['filter']) {
            return $this->searchCampaigns($params);
        }
        return $this->getCampaigns($params);
    }

    public function searchCampaigns($params)
    {
        $result = ['total' => 0, 'list' => []];

        $list = $this->getClient()->searchCampaigns($params);
        $total = (int) $list['total_items'];

        if ($total) {
            foreach ($list['results'] as $item) {
                if (isset($item['campaign'])) {
                    $campaign = $this->parseCampaign($item['campaign']);
                    if (in_array($campaign['type'], ["regular", "plaintext", "auto", "absplit"] )) {
                        $result['list'][] = $campaign;
                        $result['total']++;
                    }
                }
            }
        }

        return $result;
    }

    public function getCampaigns($params)
    {
        $result = ['total' => 0, 'list' => []];

        $list = $this->getClient()->getCampaigns($params);
        $result['total'] = (int) $list['total_items'];

        if ($result['total']) {
            foreach ($list['campaigns'] as $element) {
                $result['list'][] = $this->parseCampaign($element);
            }
        }
        return $result;
    }

    public function getListsByOffset($params)
    {
        $result = ['total' => 0, 'list' => []];

        $list = $this->getClient()->getLists($params);
        $result['total'] = (int) $list['total_items'];

        if ($result['total']) {
            foreach ($list['lists'] as $element) {
                $result['list'][] = [
                    'id' => $element['id'],
                    'name' => $element['name'],
                    'subscribers' => $element['stats']['member_count']
                ];
            }
        }
        return $result;
    }

    public function getListGroupsTree($listId)
    {
        $result = [];

        $categories = $this->getClient()->getListGroupCategories($listId);

        if (isset($categories['categories'])) {
            $i = 1;
            foreach ($categories['categories'] as $category) {
                $grouping = new \StdClass();
                $grouping->id = $category['id'];
                $grouping->name = $category['title'];
                $grouping->order = $i;
                $grouping->childList = [];

                $groupList = $this->getClient()->getListGroups($listId, $category['id']);

                if (!isset($groupList['interests'])) {
                    continue;
                }

                foreach ($groupList['interests'] as $interest) {
                    $group = new \StdClass();
                    $group->id = $interest['id'];
                    $group->name = $interest['name'];
                    $group->order = $interest['display_order'];
                    $group->parentId = $category['id'];
                    $group->parentName = $category['title'];

                    $grouping->childList[] = $group;
                }

                $result[] = $grouping;
                $i++;
            }
        }
        return $result;
    }

    public function getLists()
    {
        $result = [];

        $list = $this->getClient()->getLists();

        if (isset($list['lists']) && count($list['lists'])) {
            foreach ($list['lists'] as $element) {
                $result[$element['id']] = $element['name'];
            }
        }
        return $result;
    }

    public function getCampaign($id)
    {
        $result = $this->getClient()->getCampaign($id);
        if (is_array($result) && isset($result['id'])) {
            return $this->parseCampaign($result);
        }
        return false;
    }

    protected function parseCampaign($item)
    {
        return array(
            'id' => $item['id'],
            'name' => $item['settings']['title'],
            'type' => $item['type'],
            'status' => $item['status'],
            'webId' => $item['web_id'],
            'dateSent' => $item['send_time'],
            'listId' => $item['recipients']['list_id'],
            'fromName' => $item['settings']['from_name'],
            'fromAddress' => $item['settings']['reply_to'],
            'subject' => @$item['settings']['subject_line']
        );
    }

    public function createList(array $data)
    {
        $name = (isset($data['name'])) ? $data['name'] : '';
        $reminder = (isset($data['reminder'])) ? $data['reminder'] : '';

        $contact = new \StdClass();
        $contact->company = (isset($data['company'])) ? $data['company'] : '';
        $contact->address1 = (isset($data['address1'])) ? $data['address1'] : '';
        $contact->address2 = (isset($data['address2'])) ? $data['address2'] : '';
        $contact->city =(isset($data['city'])) ? $data['city'] : '';
        $contact->state = (isset($data['state'])) ? $data['state'] : '';
        $contact->zip = (isset($data['zip'])) ? $data['zip'] : '';
        $contact->country = (isset($data['country'])) ? $data['country'] : '';
        $contact->phone = (isset($data['phone'])) ? $data['phone'] : '';

        $campaignDefaults = new \StdClass();
        $campaignDefaults->from_name = (isset($data['fromName'])) ? $data['fromName'] : '';
        $campaignDefaults->from_email = (isset($data['fromEmail'])) ? $data['fromEmail'] : '';
        $campaignDefaults->subject = (isset($data['subject'])) ? $data['subject'] : '';
        $campaignDefaults->language = (isset($data['language'])) ? $data['language'] : 'en';

        return $this->getClient()->createList($name, $contact, $campaignDefaults, $reminder);
    }

    public function createCampaign(array $data)
    {
        $type = (isset($data['type'])) ? $data['type'] : '';
        $listId = (isset($data['listId'])) ? $data['listId'] : '';
        $title = (isset($data['name'])) ? $data['name'] : '';
        $subject = (isset($data['subject'])) ? $data['subject'] : '';
        $fromEmail = (isset($data['fromEmail'])) ? $data['fromEmail'] : '';
        $fromName = (isset($data['fromName'])) ? $data['fromName'] : '';
        $toName = (isset($data['toName'])) ? $data['toName'] : '';

        return $this->getClient()->createCampaign($type, $listId, $title, $subject, $fromEmail, $fromName, $toName);
    }

    public function addEntityFieldsToList($listId)
    {
        $mergeFieldList = $this->getRecipientHelper()->getMergeFieldList();
        $vars = $this->getVarList($listId);
        foreach ($mergeFieldList as $field) {
            if (!in_array($field, $vars)) {
                $varDefs = $this->getRecipientHelper()->getMergeFieldDefs($field);
                if ($varDefs) {
                    $this->addVarToList($listId, $varDefs);
                }
            }
        }
    }

    public function getEmailContent($campaignId)
    {
        $result = $this->getClient()->getCampaignContent($campaignId);
        if (isset($result['_links'])) {
            unset($result['_links']);
        }
        return $result;
    }

    public function getVarList($listId)
    {
        $vars = [];
        $result = $this->getClient()->getVarList($listId);
        foreach ($result['merge_fields'] as $var) {
            $vars[] = $var["tag"];
        }
        return $vars;
    }

    public function addVarToList($listId, $varDefs)
    {
        $this->getClient()->addVarToList($listId, $varDefs);
    }

    public function subscribe($listId, $params)
    {
        return $this->getClient()->subscribe($listId, $params);
    }

    public function updateMember($listId, $params)
    {
        return $this->getClient()->updateMember($listId, $params);
    }

    public function deleteMember($listId, $email)
    {
        return $this->getClient()->unsubscribe($listId, $email);
    }

    public function getMembersEmailList($listId, $beforeOptIn = null, $offset = 0)
    {
        $fixedCount = 10;
        $maxMemberCount = self::MAX_BATCH_PORTION;

        $espoMembers = [];
        $total = 0;
        $pageCount = 0;

        $params = ['offset' => $offset];

        if ($beforeOptIn) {
            try {
                if (is_object($beforeOptIn) && is_a($beforeOptIn, 'DateTime')) {
                    $dateTime = clone $beforeOptIn;
                } else {
                    $dateTime = new \DateTime($beforeOptIn);
                }
                $params['before_timestamp_opt'] = $dateTime->format('Y-m-d h:i:s');
            } catch (\Exception $e){
                $GLOBALS['log']->error('MailChimp: Can not be converted to Date param $beforeOptIn ' . $beforeOptIn);
            }
        }
        while ($maxMemberCount - count($espoMembers) > $fixedCount) {
            $result = $this->getClient()->getListMembers($listId, $params);
            if (isset($result['members']) && count($result['members'])) {
                $total = $result['total_items'];
                $pageCount = count($result['members']);
                foreach ($result['members'] as $mcMember) {
                    $espoMember = $this->getRecipientHelper()->recognizeMCMember($mcMember);
                    if ($espoMember) {
                        $espoMembers[$mcMember['email_address']] = $espoMember;
                    }
                }
                $params['offset'] += $pageCount;
            } else {
                break;
            }
        }
        return ['total' => $total, 'members' => $espoMembers, 'seenOffset' => $params['offset']];
    }

    public function getSentTo($campaignId, $offset = 0)
    {
        $espoMembers = [];
        $total = 0;
        $pageCount = 0;

        $result = $this->getClient()->getSentTo($campaignId, $offset);

        if (isset($result['sent_to'])) {
            $total = $result['total_items'];
            $pageCount = count($result['sent_to']);
            foreach ($result['sent_to'] as $mcMember) {
                $espoMember = $this->getRecipientHelper()->recognizeMCMember($mcMember);
                if ($espoMember) {
                    $espoMembers[$mcMember['email_address']] = $espoMember;
                }
            }
        } else {
           // throw new
        }
        return ['total' => $total, 'members' => $espoMembers , 'pageCount' => $pageCount];
    }

    public function getCampaignActivity($campaignId, $offset = 0)
    {
        $espoMembers = [];
        $total = 0;
        $pageCount = 0;

        $result = $this->getClient()->getCampaignActivity($campaignId, $offset);

        if (isset($result['emails'])) {
            $total = $result['total_items'];
            $pageCount = count($result['emails']);
            foreach ($result['emails'] as $mcMember) {
                $espoMember = $this->getRecipientHelper()->recognizeMCMember($mcMember);
                if ($espoMember) {
                    $espoMembers[$mcMember['email_address']] = [
                        'entity' => $espoMember,
                        'activity' => $mcMember['activity']
                    ];
                }
            }
        } else {
           // throw new
        }
        return ['total' => $total, 'members' => $espoMembers, 'pageCount' => $pageCount];
    }

    public function getUnsubscribedMembers($listId)
    {
        $offset = 0;
        $result = [];
        while (true) {
            $response = $this->getClient()->getUnsubscribedMembersFromList($listId, $offset);
            if (isset($response['members']) && count($response['members']) > 0) {
                foreach ($response['members'] as $member) {
                    $espoMember = $this->getRecipientHelper()->recognizeMCMember($member);
                    if (!empty($espoMember)) {
                        $result[$member['email_address']] = $espoMember;
                    }
                }
                $offset += count($response['members']);
            } else {
                break;
            }
        }
        return $result;
    }

    public function getCampaignOptedOutReport($campaignId, $offset = 0)
    {
        $espoMembers = [];
        $timestamps = [];
        $pageCount = 0;
        $total = 0;

        $response = $this->getClient()->getCampaignOptedOutReport($campaignId, $offset);
        if (isset($response['unsubscribes']) && count($response['unsubscribes']) > 0) {

            $total = $response['total_items'];
            $pageCount = count($response['unsubscribes']);

            foreach ($response['unsubscribes'] as $member) {

                $espoMember = $this->getRecipientHelper()->recognizeMCMember($member);
                if (!empty($espoMember)) {
                    $espoMembers[$member['email_address']] = $espoMember;
                    $timestamps[$member['email_address']] = $member['timestamp'];
                }
            }
        }
        return [
            'total' => $total,
            'members' => $espoMembers,
            'pageCount' => $pageCount,
            'timestamps' => $timestamps
        ];
    }

    public function getSentToReportByRecipients($campaign, $recipientList)
    {
        $operationPrefix = 'sent-to';
        $parseMethodName = 'SentToReport';
        $getParamsClientMethod = 'getParamsForSentToByEmail';
        $storeBatch = true;

        return $this->batchBuilder($campaign, $recipientList, $operationPrefix, $getParamsClientMethod, $storeBatch, $parseMethodName);
    }

    public function getActivitiesReportByRecipients($campaign, $recipientList)
    {
        $operationPrefix = 'email-activity';
        $parseMethodName = 'EmailActivityReport';
        $getParamsClientMethod = 'getParamsForEmailActivity';
        $storeBatch = true;

        return $this->batchBuilder($campaign, $recipientList, $operationPrefix, $getParamsClientMethod, $storeBatch, $parseMethodName);
    }

    protected function batchBuilder($campaign, $recipientList, $operationPrefix, $getParamsClientMethod, $storeBatch = false, $parseMethodName = null)
    {
        if (!$recipientList) {
            return false;
        }

        if (!method_exists($this->getClient(), $getParamsClientMethod)) {
            $GLOBALS['log']->error("MailChimp Client has not method " . $getParamsClientMethod);
            return false;
        }

        $campaignId = $campaign->get('mailChimpCampaignId');
        $operations = [];
        $counter = 0;

        $batchFields = null;
        if ($storeBatch) {
            $batchFields = [
                'method' => $parseMethodName,
                'parentType' => 'Campaign',
                'parentId' => $campaign->id
            ];
        }
        foreach ($recipientList as $recipient) {
            $email = $recipient->get('emailAddress');
            if (empty($email)) {
                continue;
            }
            $operationId = $operationPrefix . '_' . $recipient->getEntityType() . '_' . $recipient->id;
            $operation = (object) $this->getClient()->$getParamsClientMethod($campaignId, $email, $operationId);
            $operations[] = $operation;
            $counter++;

            if ($counter == self::MAX_BATCH_PORTION) {
                $this->addBatchItemToQueue($campaign, $operations, $batchFields);
                $operations = [];
                $counter = 0;
            }
        }
        if (count($operations)) {
            $this->addBatchItemToQueue($campaign, $operations, $batchFields);
        }
        return true;
    }

    public function sendBatchRequest($operations)
    {
        try {
            $batchResult = $this->getClient()->batches($operations);
            return $batchResult['id'];
        } catch (\Exception $e) {
            $GLOBALS['log']->error($e->getMessage());
            return false;
        }
    }

    public function parseQueueItemsMembers($items)
    {
        $currentBatchItem = null;

        foreach ($items as $item) {

            $item->set('status', 'Running');
            $this->getEntityManager()->saveEntity($item);

            $recipient = ['scope' => $item->get('recipientEntityType'), 'id' => $item->get('recipientEntityId')];
            $targetList = $this->getEntityManager()->getEntity($item->get('parentType'), $item->get('parentId'));
            $operation = $this->formatMemberOperationFromQueue($item, $targetList, $recipient, $item->get('additionalData'));

            if ($operation) {
                $currentBatchItem = $this->addOpperationToBatch($currentBatchItem, $operation);
                $item->set('relatedItemId', $currentBatchItem->id);
                $item->set('status', 'Success');
            } else {
                $item->set('status', 'Failed');
            }
            $this->getEntityManager()->saveEntity($item);
        }

        return true;
    }

    public function parseQueueItemsUpdateList($items)
    {
        foreach ($items as $item) {
            $item->set('status', 'Running');
            $this->getEntityManager()->saveEntity($item);

            $targetList = $this->getEntityManager()->getEntity($item->get('parentType'), $item->get('parentId'));
            if ($targetList && $targetList->get('mailChimpListId')) {
                $listId = $targetList->get('mailChimpListId');

                $batchFields = [
                    'method' => "SubscribeRecipients",
                    'parentType' => 'TargetList',
                    'parentId' => $targetList->id,
                    'relatedItemId' => $item->id
                ];

                $currentBatchItem = null;
                $marker = $this->getEntityManager()->getRepository('MailChimpLogMarker')->findMarker($targetList->id, 'TargetList');
                $markerData = $marker->get('data');
                $recipientList = $this->getRecipientHelper()->getTargetListRecipients($targetList, $markerData);

                foreach ($recipientList as $recipient) {
                    $recipientEntity = $this->getEntityManager()->getEntity($recipient['scope'], $recipient['id']);
                    if (!$recipientEntity) {
                       continue;
                    }

                    $parsedRecipient = $this->getRecipientHelper()->prepareRecipientToMailChimp($recipientEntity);
                    $subscribeElem = $this->getRecipientHelper()->formatSubscriber($parsedRecipient, $targetList->get('mcListGroupId'));
                    if (!$subscribeElem) {
                        return false;
                    }

                    $operationId = 'subscribe_' . $recipient['scope'] . '_' . $recipient['id'];
                    $operation = (object) $this->getClient()->getParamsForSubscription($listId, $subscribeElem, $operationId);

                    if (!empty($operation)) {
                        $currentBatchItem = $this->addOpperationToBatch($currentBatchItem, $operation, $batchFields);
                    }
                }

                $marker->set('data', $this->getRecipientHelper()->getLastRelsIds());
                $this->getEntityManager()->saveEntity($marker);

                $item->set('status', 'Success');
            } else {
                $item->set('status', 'Failed');
            }
            $this->getEntityManager()->saveEntity($item);
        }
    }

    protected function formatMemberOperationFromQueue($item, $targetList, $recipient, $options = null)
    {
        if (empty($targetList) || !$targetList->get('mailChimpListId')) {
            return false;
        }

        $listId = $targetList->get('mailChimpListId');
        $recipientEntity = $this->getEntityManager()->getEntity($recipient['scope'], $recipient['id']);

        switch ($item->get('name')) {
            case 'Subscribe':
                if (!$recipientEntity) {
                   return false;
                }
                $parsedRecipient = $this->getRecipientHelper()->prepareRecipientToMailChimp($recipientEntity);
                $subscribeElem = $this->getRecipientHelper()->formatSubscriber($parsedRecipient, $targetList->get('mcListGroupId'));
                if (!$subscribeElem) {
                    return false;
                }

                $operationId = 'subscribe_QueueItem_' . $item->id;
                $operation = (object) $this->getClient()->getParamsForSubscription($listId, $subscribeElem, $operationId);
                break;
            case 'UpdateMember':
                if (!$recipientEntity) {
                   return false;
                }
                $parsedRecipient = $this->getRecipientHelper()->prepareRecipientToMailChimp($recipientEntity);

                if (is_object($options) && isset($options->oldEmailAddress)) {
                    $oldEmailAddress = $options->oldEmailAddress;
                    if (!empty($oldEmailAddress)) {
                        $parsedRecipient['oldEmailAddress'] = $oldEmailAddress;
                    }
                }
                $subscribeElem = $this->getRecipientHelper()->formatSubscriber($parsedRecipient, $targetList->get('mcListGroupId'));
                if (!$subscribeElem) {
                    return false;
                }
                $operationId = 'update_QueueItem_' . $item->id;
                $operation = (object) $this->getClient()->getParamsForUpdateMember($listId, $subscribeElem, $operationId);
                break;
            case 'Unsubscribe':
                if (is_object($options) && isset($options->emailAddress)) {
                    $emailAddress = $options->emailAddress;
                }
                if (isset($emailAddress)) {
                    $parsedRecipient = ['emailAddress' => $emailAddress];
                    $subscribeElem = $this->getRecipientHelper()->formatSubscriber($parsedRecipient, $targetList->get('mcListGroupId'), false);
                    $operationId = 'unsubscribe_QueueItem_' . $item->id;
                    $operation = (object) $this->getClient()->getParamsForUnsubscribe($listId, $subscribeElem, $operationId);
                }
                break;
        }

        return (isset($operation)) ? $operation : false;
    }

    public function getBatchResult($batch)
    {
        $batchResult = $this->getClient()->getBatch($batch->get('name'));
        if (isset($batchResult['status']) && $batchResult['status'] == 'finished') {

            $batchRepository = $this->getEntityManager()->getRepository('MailChimpBatch');
            $unziped = $batchRepository->saveContentFromUrl($batch, $batchResult['response_body_url']);

            if ($unziped) {
                $result = $this->loadDataFromBatchSentToResult($batchRepository->getDataPath($batch));
                if (isset($result['failed'])) {
                    foreach ($result['failed'] as $res) {
                        if (isset($res['id'])) {
                            $item = $this->getEntityManager()->getEntity('MailchimpQueue', $res['id']);
                            if ($item) {
                                $item->set('status', 'Failed');
                                $this->getEntityManager()->saveEntity($item);
                            }
                        }
                        $GLOBALS['log']->debug('MailChimp: Error in batch item: ' . print_r($res, true));
                    }
                }
                if (isset($result['success'])) {
                    return $result['success'];
                }
                return true;
            }
        }
        return false;
    }

    private function loadDataFromBatchSentToResult($dirPath)
    {
        $fileList = $this->getFileManager()->getFileList($dirPath);
        $successResult = [];
        $failedResult = [];
        if (is_array($fileList)) {
            foreach ($fileList as $file) {
                $fileContent = $this->getFileManager()->getContents([$dirPath, $file]);
                $json = json_decode($fileContent);
                if (is_array($json)) {
                    foreach ($json as $operation) {
                        if (isset($operation->status_code) && isset($operation->operation_id)) {
                            list($operationType, $entityType, $entityId) = explode('_', $operation->operation_id);
                            if ($operation->status_code == "200") {
                                $operationResponse = json_decode($operation->response);
                                if ($operationResponse) {
                                    if ($entityType == 'QueueItem') {
                                        $item = $this->getEntityManager()->getEntity('MailchimpQueue', $entityId);
                                        if ($item) {
                                            $entityType = $item->get('recipientEntityType');
                                            $entityId = $item->get('recipientEntityId');
                                        }
                                    }
                                    $emailAddress = (isset($operationResponse->email_address)) ? $operationResponse->email_address : '';
                                    $successResult[] = [
                                        'entityType' => $entityType,
                                        'entityId' => $entityId,
                                        'emailAddress' => $emailAddress,
                                        'response' => $operationResponse,
                                        'operationType' => $operationType
                                    ];
                                }
                            } else {
                                $operationResponse = json_decode($operation->response);
                                $detail = (isset($operationResponse->detail)) ? $operationResponse->detail : '';
                                $status = (isset($operationResponse->status)) ? $operationResponse->status : '';
                                if ($entityType == 'QueueItem') {
                                    $failedResult[] = [
                                        'id' => $entityId,
                                        'detail' => $detail,
                                        'code' => $status
                                    ];
                                } else {
                                    $failedResult[] = [
                                        'entityType' => $entityType,
                                        'entityId' => $entityId,
                                        'operationType' => $operationType,
                                        'detail' => $detail,
                                        'code' => $status
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        return ['success' => $successResult, 'failed' => $failedResult];
    }

    public function addOpperationToBatch($batchItem, $operation, $batchFields = null, $parent = null)
    {
        if (!$batchItem) {
            $batchItem = $this->getEntityManager()->getEntity('MailChimpQueue');
            $batchItem->set('name', 'Batch');
            if (is_array($batchFields) && count($batchFields)) {
                $batchItem->set($batchFields);
                $batchItem->set('additionalData', $batchFields);
            }
        }

        $data = $batchItem->get('data');
        $operations = (is_array($data)) ? $data : [];
        if (count($operations) >= self::MAX_BATCH_PORTION) {
            return $this->addOpperationToBatch(null, $operation, $batchFields);
        }
        $operations[] = $operation;
        $batchItem->set('data', $operations);
        $this->getEntityManager()->saveEntity($batchItem);
        return $batchItem;
    }

    public function addBatchItemToQueue($parent, array $operations, $batchFields = null)
    {
        $item = $this->getEntityManager()->getEntity('MailChimpQueue');

        $item->set([
            'name' => 'Batch',
            'data' => $operations,
            'additionalData' => $batchFields,
            'parentType' => $parent->getEntityType(),
            'parentId' => $parent->id,
        ]);
        $this->getEntityManager()->saveEntity($item);
        return $item;
    }

    public function addUpdateMemberItemToQueue($targetList, $member, $oldEmailAddress = null)
    {
        $data = [
            'status' => 'Pending',
            'parentType' => $targetList->getEntityType(),
            'parentId' => $targetList->id,
            'recipientEntityType' => $member->getEntityType(),
            'recipientEntityId' => $member->id
        ];

        $subscribeData = $data;
        $subscribeData['name'] = 'Subscribe';
        $item = $this->getEntityManager()->getRepository('MailChimpQueue')->where($subscribeData)->findOne();
        if ($item) {
            return false;
        }

        $updateData = $data;
        $updateData['name'] = 'UpdateMember';
        $item = $this->getEntityManager()->getRepository('MailChimpQueue')->where($updateData)->findOne();

        if (!$item) {
            $item = $this->getEntityManager()->getEntity('MailChimpQueue');
            $item->set($updateData);
            $item->set('additionalData', ['oldEmailAddress' => $oldEmailAddress]);
            $this->getEntityManager()->saveEntity($item);
        }
        return $item;
    }

    public function addUnsubscribeItemToQueue($targetList, $member)
    {
        $data = [
            'status' => 'Pending',
            'parentType' => $targetList->getEntityType(),
            'parentId' => $targetList->id,
            'recipientEntityType' => $member->getEntityType(),
            'recipientEntityId' => $member->id
        ];

        $subscribeData = $data;
        $subscribeData['name'] = 'Subscribe';
        $item = $this->getEntityManager()->getRepository('MailChimpQueue')->where($subscribeData)->findOne();
        if ($item) {
            $this->getEntityManager()->removeEntity($item);
            return false;
        }

        $updateData = $data;
        $updateData['name'] = 'UpdateMember';
        $item = $this->getEntityManager()->getRepository('MailChimpQueue')->where($updateData)->findOne();
        if ($item) {
            $additionalData = $item->get('additionalData');
            if (isset($additionalData->oldEmailAddress)) {
                $emailAddress = $additionalData->oldEmailAddress;
            }
            $this->getEntityManager()->removeEntity($item);
        }

        $unsubcribeData = $data;
        $unsubcribeData['name'] = 'Unsubscribe';

        $item = $this->getEntityManager()->getRepository('MailChimpQueue')->where($unsubcribeData)->findOne();

        if (!$item) {
            $item = $this->getEntityManager()->getEntity('MailChimpQueue');
            $item->set($unsubcribeData);
            if (!isset($emailAddress)) {
                $emailAddress = $member->get('emailAddress');
            }
            $item->set('additionalData', ['emailAddress' => $emailAddress]);
            $this->getEntityManager()->saveEntity($item);
        }
        return $item;
    }

    public function addSubscribeItemToQueue($targetList, $member)
    {
        $data = [
            'status' => 'Pending',
            'parentType' => $targetList->getEntityType(),
            'parentId' => $targetList->id,
            'recipientEntityType' => $member->getEntityType(),
            'recipientEntityId' => $member->id
        ];

        $unsubcribeData = $data;
        $unsubcribeData['name'] = 'Unsubscribe';
        $item = $this->getEntityManager()->getRepository('MailChimpQueue')->where($unsubcribeData)->findOne();
        if ($item) {
            $this->getEntityManager()->removeEntity($item);
            return false;
        }

        $subscribeData = $data;
        $subscribeData['name'] = 'Subscribe';

        $item = $this->getEntityManager()->getRepository('MailChimpQueue')->where($subscribeData)->findOne();

        if (!$item) {
            $item = $this->getEntityManager()->getEntity('MailChimpQueue');
            $item->set($subscribeData);
            $this->getEntityManager()->saveEntity($item);
        }
        return $item;
    }

    public function addUpdateListItemToQueue($targetList, $byUserRequest = false)
    {
        $data = [
            'name' => 'UpdateList',
            'status' => 'Pending',
            'parentType' => $targetList->getEntityType(),
            'parentId' => $targetList->id,
        ];

        $item = $this->getEntityManager()->getRepository('MailChimpQueue')->where($data)->findOne();

        if (!$item) {
            $item = $this->getEntityManager()->getEntity('MailChimpQueue');
            $item->set($data);
            $this->getEntityManager()->saveEntity($item);
        }
        if ($byUserRequest) {
            $marker = $this->getEntityManager()->getRepository('MailChimpLogMarker')->findMarker($targetList->id, 'TargetList');
            if ($marker) {
                $this->getEntityManager()->removeEntity($marker);
            }
        }
        return $item;
    }

}
