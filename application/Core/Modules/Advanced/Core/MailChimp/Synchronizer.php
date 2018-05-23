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

class Synchronizer
{
    protected $entityManager = null;
    protected $metadata = null;
    protected $mailChimpManager = null;
    protected $syncSettings = null;

    public function __construct($entityManager, $metadata, $mailChimpManager)
    {
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->mailChimpManager = $mailChimpManager;
        $this->loadSyncSettings();
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

    protected function loadSyncSettings()
    {
        $integration = $this->getEntityManager()->getEntity('Integration', 'MailChimp');
        if (empty($integration)) {
            throw new Forbidden("MailChimp integration is not configured");
        }
        $this->syncSettings = new \StdClass();
        $limitDay = (int) $integration->get('logSyncDurationDays');
        if ($limitDay <= 0) {
            $limitDay = $this->getMetadata()->get(['integrations', 'MailChimp', 'fields', 'logSyncDurationDays', 'default']);
        }

        $this->syncSettings->limitDay = $limitDay;
        $this->syncSettings->createEmails = $integration->get('createEmails');
        $this->syncSettings->markEmailsOptedOut = $integration->get('markEmailsOptedOut');
        $this->syncSettings->hardBouncedAction = $integration->get('hardBouncedAction');

    }

    protected function getMarker($campaign, $markerType)
    {
        return $this->getEntityManager()->getRepository('MailChimpLogMarker')->findMarker($campaign->get('mailChimpCampaignId'), $markerType);
    }

    protected function addLogRecord(array $logFields)
    {
        $duplicateFields = $logFields;
        unset($duplicateFields['actionDate']);

        $logRecord = $this->getEntityManager()->getRepository('CampaignLogRecord')->where($duplicateFields)->findOne();
        if (!$logRecord) {
            $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');
            $logRecord->set($logFields);
            $this->getEntityManager()->saveEntity($logRecord);
        }
        return $logRecord;
    }

    protected function createMemberEmail(Entity $campaign, Entity $member, $memberEmail, $sentTime, $emailInfo)
    {
        if (isset($emailInfo['plain_text']) || isset($emailInfo['html'])) {
            $email = $this->getEntityManager()->getEntity('Email');
            $subject = (isset($emailInfo['subject'])) ? $emailInfo['subject'] : $campaign->get('mailChimpCampaignName');
            $email->set('name', $subject);
            if (isset($emailInfo['html'])) {
                $email->set('body', $emailInfo['html']);
            } else {
                $email->set('body', $emailInfo['plain_text']);
                $email->set('isHtml', false);
            }
            $email->set('bodyPlain', $emailInfo['plain_text']);
            $email->set('parentId', $member->id);
            $email->set('parentType', $member->getEntityType());
            $email->set('to', $memberEmail);
            $email->set('from', $emailInfo['fromAddress']);
            $email->set('status', 'Sent');
            $email->set('dateSent', $sentTime);
            $this->getEntityManager()->saveEntity($email, ['silent' => true]);
            return $email;
        }
        return false;
    }

    protected function campaignOptedOutMembers($campaign, $espoListsNames, $mcListId, $markOptedOut = false)
    {
        $reportType = 'Opted Out';
        $marker = $this->getMarker($campaign, $reportType);
        $offset = $marker->get('offset');
        $reportResult = $this->getMailChimpManager()->getCampaignOptedOutReport($campaign->get('mailChimpCampaignId'), $offset);
        if (empty($reportResult['pageCount'])) {
            return;
        }
        foreach ($reportResult['members'] as $memberEmail => $memberEntity) {
            $actionDate = new \DateTime($reportResult['timestamps'][$memberEmail]);
            $logFields = [
                'campaignId' => $campaign->id,
                'parentType' => $memberEntity->getEntityType(),
                'parentId' => $memberEntity->id,
                'application' => 'MailChimp',
                'action' => $reportType,
                'actionDate' => $actionDate->format("Y-m-d H:i:s"),
                'stringData' => $espoListsNames,
            ];
            $this->addLogRecord($logFields);
            if ($markOptedOut) {
                $emailAddressEntity = $this->getEntityManager()->getRepository('EmailAddress')->getByAddress($memberEmail);
                if (!empty($emailAddressEntity) && !$emailAddressEntity->get("optOut")) {
                    $emailAddressEntity->set("optOut", true);
                    $this->getEntityManager()->saveEntity($emailAddressEntity);
                }
            }
            $this->unsubscribeRecipientFromCoreLists($memberEntity, $mcListId);
        }
        $offset += $reportResult['pageCount'];
        $marker->set('offset', $offset);
        $this->getEntityManager()->saveEntity($marker);
        $this->campaignOptedOutMembers($campaign, $espoListsNames, $mcListId, $markOptedOut);
    }

    public function scheduleCampaignsSync()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $query = "SELECT id
            FROM campaign
            WHERE mail_chimp_campaign_id <> '' AND mail_chimp_campaign_id IS NOT NULL AND deleted=0
            ORDER BY mail_chimp_last_successful_updating";

        $sth = $pdo->prepare($query);
        $sth->execute();
        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $row) {
            $this->getEntityManager()->getRepository('MailChimp')->addSyncJob('Campaign', $row['id']);
        }
    }

    public function scheduleTargetListsSync()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $query = "SELECT id
            FROM target_list
            WHERE mail_chimp_list_id <> '' AND mail_chimp_list_id IS NOT NULL AND deleted=0
            ORDER BY mail_chimp_last_successful_updating";

        $sth = $pdo->prepare($query);
        $sth->execute();
        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $row) {
            $this->getEntityManager()->getRepository('MailChimp')->addSyncJob('TargetList', $row['id']);
        }
    }

    public function checkMergeFieldsExisting()
    {
        $pdo = $this->getEntityManager()->getPDO();
        $query = "SELECT DISTINCT(mail_chimp_list_id) as listId
            FROM target_list
            WHERE mail_chimp_list_id <> '' AND mail_chimp_list_id IS NOT NULL AND deleted=0
            ";

        $sth = $pdo->prepare($query);
        $sth->execute();
        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $row) {
            $this->getMailChimpManager()->addEntityFieldsToList($row['listId']);
        }
    }

    public function loadLogDataForCampaign(Entity $campaign, $byUserRequest = false)
    {
        $mcCampaignId = $campaign->get('mailChimpCampaignId');

        if (empty($mcCampaignId)) {
            return false;
        }

        $mailChimpCampaign = $this->getMailChimpManager()->getCampaign($mcCampaignId);
        if (empty($mailChimpCampaign)) {
            return false;
        }

        if ($campaign->get('mailChimpCampaignStatus') !== $mailChimpCampaign['status']) {
            $campaign->set('mailChimpCampaignStatus', $mailChimpCampaign['status']);
            $this->getEntityManager()->saveEntity($campaign, ['silent' => true]);
        }

        if ($mailChimpCampaign['status'] == 'sent') {
            $utc = new \DateTimeZone('UTC');
            $sentTime = new \DateTime($mailChimpCampaign['dateSent'], $utc);
            $now = new \DateTime('NOW', $utc);

            $emailInfo = [
                'fromName' => $mailChimpCampaign['fromName'],
                'fromAddress' => $mailChimpCampaign['fromAddress'],
                'subject' => $mailChimpCampaign['subject']
            ];

            if ($this->syncSettings->createEmails) {
                $content = $this->getMailChimpManager()->getEmailContent($mcCampaignId);
                $emailInfo = array_merge($emailInfo, $content);
            }

            $isUninteristingDate = !$byUserRequest && $now->diff($sentTime)->days > $this->syncSettings->limitDay;
            $sentMarker = $this->getMarker($campaign, 'Sent');

            if (!$sentMarker->get('data')) {
                $sentData = new \StdClass();
                $sentData->createEmails = $this->syncSettings->createEmails;
                $sentData->sentTime = $sentTime->format("Y-m-d H:i:s");
                $sentData->emailInfo = $emailInfo;
                $sentMarker->set('data', $sentData);
                $this->getEntityManager()->saveEntity($sentMarker);
            }
            $this->requestLogByListMember($campaign, $mailChimpCampaign['listId'], $sentTime,$isUninteristingDate);
            $lists = $this->getEntityManager()->getRepository('TargetList')
                ->where(['mailChimpListId' => $mailChimpCampaign['listId']])
                ->find();

            $targetListNamesArray = [];
            if ($lists) {
                foreach ($lists as $list) {
                    $targetListNamesArray[] = $list->get('name');
                }
            }
            $this->campaignOptedOutMembers(
                $campaign,
                implode(', ', $targetListNamesArray),
                $mailChimpCampaign['listId'],
                $this->syncSettings->markEmailsOptedOut);
            $campaign->set('mailChimpLastSuccessfulUpdating', $now->format("Y-m-d H:i:s"));
            $this->getEntityManager()->saveEntity($campaign, ['silent' => true]);
        }
        return true;
    }

    protected function requestLogByListMember($campaign, $mcListId, $sentTime, $isUninteristingDate = false)
    {
        $reportType = 'Members';
        $pdo = $this->getEntityManager()->getPDO();

        $memberMarker = $this->getMarker($campaign, 'Members');

        $sentMarker = $this->getMarker($campaign, 'Sent');
        $activityMarker = $this->getMarker($campaign, 'CampaignActivity');

        if ($sentMarker->get('completed') && $activityMarker->get('completed') && $isUninteristingDate) {
            return true;
        }

        $successfulRequestActivities = false;
        $successfulRequestSentTo = false;

        $offset = (int) $memberMarker->get('offset');
        $result = $this->getMailChimpManager()->getMembersEmailList($mcListId, $sentTime, $offset);

        if (isset($result['members']) && count($result['members'])) {
            $hitEnd = $result['total'] == $result['seenOffset'];
            if (!$sentMarker->get('completed')) {

                $successfulRequestSentTo = $this->getMailChimpManager()->getSentToReportByRecipients($campaign, $result['members']);

                if ($successfulRequestSentTo && $hitEnd) {
                    $sentMarker->set('completed', true);
                    $this->getEntityManager()->saveEntity($sentMarker);
                }
            } else {
                $successfulRequestSentTo = true;
            }

            if (!$activityMarker->get('completed') || !$isUninteristingDate) {
                $successfulRequestActivities = $this->getMailChimpManager()->getActivitiesReportByRecipients($campaign, $result['members']);
                if ($successfulRequestActivities && $hitEnd) {
                    $activityMarker->set('completed', true);
                    $this->getEntityManager()->saveEntity($activityMarker);
                }
            } else {
                $successfulRequestActivities = true;
            }

            if ($successfulRequestActivities && $successfulRequestSentTo) {
                if (!$hitEnd) {

                    $memberMarker->set('offset', $result['seenOffset']);
                    $this->getEntityManager()->saveEntity($memberMarker);

                    $this->requestLogByListMember($campaign, $mcListId, $sentTime, $isUninteristingDate);
                } else {

                    $memberMarker->set('offset', 0);
                    $this->getEntityManager()->saveEntity($memberMarker);
                }
            }
        }
    }

    public function loadBatchResult(Entity $batch)
    {
        $loadedResult = $this->getMailChimpManager()->getBatchResult($batch);
        if ($loadedResult === false) {
            return false;
        }

        $methodName = 'save' . $batch->get('method') . 'Logs';

        $entity = $this->getEntityManager()->getEntity($batch->get('parentType'), $batch->get('parentId'));

        if (method_exists($this, $methodName) && $entity) {
            $this->$methodName($entity, $loadedResult);
        }
        $this->getEntityManager()->removeEntity($batch);
        return true;
    }

    protected function saveSentToReportLogs($campaign, $memberList)
    {
        $reportType = 'Sent';
        $sentMarker = $this->getMarker($campaign, $reportType);
        $sentData = $sentMarker->get('data');
        $sentTime = $sentData->sentTime;
        $emailInfo = $sentData->emailInfo;
        foreach ($memberList as $member) {
            $logFields = array(
                'campaignId' => $campaign->id,
                'parentType' => $member['entityType'],
                'parentId' => $member['entityId'],
                'application' => 'MailChimp',
                'action' => $reportType,
                'actionDate' => $sentData->sentTime,
                'stringData' => $member['emailAddress'],
            );
            $log = $this->addLogRecord($logFields);

            if ($sentData->createEmails && !$log->get('objectId')) {
                $memberEntity = $this->getEntityManager()->getEntity($member['entityType'], $member['entityId']);
                $email = $this->createMemberEmail($campaign, $memberEntity, $member['emailAddress'], $sentData->sentTime, (array) $sentData->emailInfo);
                if (!empty($email)) {
                    $log->set('objectId', $email->id);
                    $log->set('objectType', 'Email');
                    $this->getEntityManager()->saveEntity($log);
                }
            }
        }
    }

    protected function saveEmailActivityReportLogs($campaign, $memberList)
    {
        foreach ($memberList as $member) {
            $response = $member['response'];
            $activities = $response->activity;

            if (is_array($activities) && count($activities)) {
                foreach ($activities as $activity) {

                    $activity = (object) $activity;
                    $stringAdditionalData = null;
                    $stringData = null;

                    switch ($activity->action) {
                        case 'open':
                            $action = 'Opened';
                            break;
                        case 'click':
                            $action = 'Clicked';
                            $stringData = $activity->url;
                            break;
                        case 'bounce':
                            $action = 'Bounced';
                            if ($activity->type == 'hard') {
                                $stringAdditionalData = 'Hard';

                                $reaction = $this->syncSettings->hardBouncedAction;
                                if (in_array($reaction, ["setAsInvalid", "setAsInvalidAndRemove"])) {
                                    $emailAddressEntity = $this->getEntityManager()->getRepository('EmailAddress')->getByAddress($member['emailAddress']);
                                    if (!empty($emailAddressEntity) &&
                                        !$emailAddressEntity->get("invalid")) {

                                        $emailAddressEntity->set("invalid", true);
                                        $this->getEntityManager()->saveEntity($emailAddressEntity);
                                    }
                                }
                                if (in_array($reaction, ["removeFromList", "setAsInvalidAndRemove"])) {
                                    $memberEntity = $this->getEntityManager()->getEntity($member['entityType'], $member['entityId']);
                                    if ($memberEntity) {
                                        $this->removeRecipientFromCoreLists($memberEntity, $response->list_id);
                                    }
                                }

                            } else if ($activity->type == 'soft') {
                                $stringAdditionalData = 'Soft';
                            }
                            $stringData = $member['emailAddress'];
                            break;
                        default: $action = '';
                    }

                    if ($action) {
                        $actionDate = new \DateTime($activity->timestamp);
                        $logFields = [
                            'campaignId' => $campaign->id,
                            'parentType' => $member['entityType'],
                            'parentId' => $member['entityId'],
                            'application' => 'MailChimp',
                            'action' => $action,
                            'actionDate' => $actionDate->format('Y-m-d H:i:s'),
                            'stringData' => $stringData,
                            'stringAdditionalData' => $stringAdditionalData,
                        ];
                        $this->addLogRecord($logFields);
                    }
                }
            }
        }
    }

    public function updateMCRecipients(Entity $targetList, $byUserRequest = false)
    {
        $mcListId = $targetList->get('mailChimpListId');

        if (!empty($mcListId)) {
            $this->getMailChimpManager()->addEntityFieldsToList($mcListId);

            $unsubscribedList = $this->getMailChimpManager()->getUnsubscribedMembers($mcListId);
            foreach ($unsubscribedList as $emailAddress => $member) {
                $this->unsubscribeRecipientFromCoreList($member, $targetList);
            }

            $this->getMailChimpManager()->addUpdateListItemToQueue($targetList, $byUserRequest);
        }
    }

    public function removeRecipientFromCoreLists($memberEntity, $mcListId)
    {
        $lists = $this->getEntityManager()->getRepository('TargetList')->where(['mailChimpListId' => $mcListId])->find();
        $memberRepository = $this->getEntityManager()->getRepository($memberEntity->getEntityType());
        if (!empty($lists)) {
            foreach ($lists as $list) {
                $memberRepository->unrelate($memberEntity, 'targetLists', $list);
            }
        }
    }

    public function unsubscribeRecipientFromCoreLists($memberEntity, $mcListId)
    {
        $lists = $this->getEntityManager()->getRepository('TargetList')->where(['mailChimpListId' => $mcListId])->find();
        if (!empty($lists)) {
            foreach ($lists as $list) {
                $this->unsubscribeRecipientFromCoreList($memberEntity, $list);
            }
        }
    }

    public function unsubscribeRecipientFromCoreList($member, $targetList)
    {
        $this->getEntityManager()->getRepository($member->getEntityType())
            ->updateRelation($member, 'targetLists', $targetList->id, ['optedOut' => 1]);
    }

}
