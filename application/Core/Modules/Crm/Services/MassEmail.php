<?php


namespace Core\Modules\Crm\Services;

use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\BadRequest;

use \Core\ORM\Entity;

class MassEmail extends \Core\Services\Record
{
    const MAX_ATTEMPT_COUNT = 3;

    const MAX_PER_HOUR_COUNT = 100;

    private $campaignService = null;

    private $emailTemplateService = null;

    protected function init()
    {
        parent::init();
        $this->addDependency('container');
        $this->addDependency('language');
    }

    protected function getMailSender()
    {
        return $this->getInjection('container')->get('mailSender');
    }

    protected function getLanguage()
    {
        return $this->getInjection('language');
    }

    protected function beforeCreate(Entity $entity, array $data = array())
    {
        parent::beforeCreate($entity, $data);
        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }
    }

    protected function afterRemove(Entity $massEmail, array $data = array())
    {
        parent::afterRemove($massEmail, $data);
        $existingQueueItemList = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
            'status' => ['Pending', 'Failed'],
            'massEmailId' => $massEmail->id
        ))->find();
        foreach ($existingQueueItemList as $existingQueueItem) {
            $this->getEntityManager()->getMapper('RDB')->deleteFromDb('EmailQueueItem', $existingQueueItem->id);
        }
    }

    public function createQueue(Entity $massEmail, $isTest = false, $additionalTargetList = [])
    {
        if (!$isTest && $massEmail->get('status') !== 'Pending') {
            throw new Error("Mass Email '".$massEmail->id."' should be 'Pending'.");
        }

        if (!$isTest) {
            $existingQueueItemList = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
                'status' => ['Pending', 'Failed'],
                'massEmailId' => $massEmail->id
            ))->find();
            foreach ($existingQueueItemList as $existingQueueItem) {
                $this->getEntityManager()->getMapper('RDB')->deleteFromDb('EmailQueueItem', $existingQueueItem->id);
            }
        }

        $metTargetHash = array();
        $metEmailAddressHash = array();
        $entityList = [];

        $pdo = $this->getEntityManager()->getPDO();

        if (!$isTest) {
            $excludingTargetListList = $massEmail->get('excludingTargetLists');
            foreach ($excludingTargetListList as $excludingTargetList) {
                foreach (['accounts', 'contacts', 'leads', 'users'] as $link) {
                    foreach ($excludingTargetList->get($link) as $excludingTarget) {
                        $hashId = $excludingTarget->getEntityType() . '-'. $excludingTarget->id;
                        $metTargetHash[$hashId] = true;
                        $emailAddress = $excludingTarget->get('emailAddress');
                        if ($emailAddress) {
                            $metEmailAddressHash[$emailAddress] = true;
                        }
                    }
                }
            }

            $targetListCollection = $massEmail->get('targetLists');
            foreach ($targetListCollection as $targetList) {
                $accountList = $targetList->get('accounts', array(
                    'additionalColumnsConditions' => array(
                        'optedOut' => false
                    )
                ));
                foreach ($accountList as $account) {
                    $hashId = $account->getEntityType() . '-'. $account->id;
                    $emailAddress = $account->get('emailAddress');
                    if (empty($emailAddress)) {
                        continue;
                    }
                    if (!empty($metEmailAddressHash[$emailAddress])) {
                        continue;
                    }
                    if (!empty($metTargetHash[$hashId])) {
                        continue;
                    }

                    $entityList[] = $account;
                    $metTargetHash[$hashId] = true;
                    $metEmailAddressHash[$emailAddress] = true;
                }

                $contactList = $targetList->get('contacts', array(
                    'additionalColumnsConditions' => array(
                        'optedOut' => false
                    )
                ));
                foreach ($contactList as $contact) {
                    $hashId = $contact->getEntityType() . '-'. $contact->id;
                    $emailAddress = $contact->get('emailAddress');
                    if (empty($emailAddress)) {
                        continue;
                    }
                    if (!empty($metEmailAddressHash[$emailAddress])) {
                        continue;
                    }
                    if (!empty($metTargetHash[$hashId])) {
                        continue;
                    }

                    $entityList[] = $contact;
                    $metTargetHash[$hashId] = true;
                    $metEmailAddressHash[$emailAddress] = true;
                }

                $leadList = $targetList->get('leads', array(
                    'additionalColumnsConditions' => array(
                        'optedOut' => false
                    )
                ));
                foreach ($leadList as $lead) {
                    $hashId = $lead->getEntityType() . '-'. $lead->id;
                    $emailAddress = $lead->get('emailAddress');
                    if (empty($emailAddress)) {
                        continue;
                    }
                    if (!empty($metEmailAddressHash[$emailAddress])) {
                        continue;
                    }
                    if (!empty($metTargetHash[$hashId])) {
                        continue;
                    }

                    $entityList[] = $lead;
                    $metTargetHash[$hashId] = true;
                    $metEmailAddressHash[$emailAddress] = true;
                }

                $userList = $targetList->get('users', array(
                    'additionalColumnsConditions' => array(
                        'optedOut' => false
                    )
                ));
                foreach ($userList as $user) {
                    $hashId = $user->getEntityType() . '-'. $user->id;
                    $emailAddress = $user->get('emailAddress');
                    if (empty($emailAddress)) {
                        continue;
                    }
                    if (!empty($metEmailAddressHash[$emailAddress])) {
                        continue;
                    }
                    if (!empty($metTargetHash[$hashId])) {
                        continue;
                    }

                    $entityList[] = $user;
                    $metTargetHash[$hashId] = true;
                    $metEmailAddressHash[$emailAddress] = true;
                }
            }
        }

        foreach ($additionalTargetList as $target) {
            $entityList[] = $target;
        }

        foreach ($entityList as $target) {
            $emailAddress = $target->get('emailAddress');
            if (!$target->get('emailAddress')) continue;
            $emailAddressRecord = $this->getEntityManager()->getRepository('EmailAddress')->getByAddress($emailAddress);
            if ($emailAddressRecord) {
                if ($emailAddressRecord->get('invalid') || $emailAddressRecord->get('optOut')) {
                    continue;
                }
            }

            $queueItem = $this->getEntityManager()->getEntity('EmailQueueItem');
            $queueItem->set(array(
                'massEmailId' => $massEmail->id,
                'status' => 'Pending',
                'targetId' => $target->id,
                'targetType' => $target->getEntityType(),
                'isTest' => $isTest
            ));
            $this->getEntityManager()->saveEntity($queueItem);
        }

        if (!$isTest) {
            $massEmail->set('status', 'In Process');
            if (empty($entityList)) {
                $massEmail->set('status', 'Complete');
            }

            $this->getEntityManager()->saveEntity($massEmail);
        }
    }

    protected function setFailed(Entity $massEmail)
    {
        $massEmail->set('status', 'Failed');
        $this->getEntityManager()->saveEntity($massEmail);

        $queueItemList = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
            'status' => 'Pending',
            'massEmailId' => $massEmail->id
        ))->find();
        foreach ($queueItemList as $queueItem) {
            $queueItem->set('status', 'Failed');
            $this->getEntityManager()->saveEntity($queueItem);
        }
    }

    public function processSending(Entity $massEmail, $isTest = false)
    {
        $maxBatchSize = $this->getConfig()->get('massEmailMaxPerHourCount', self::MAX_PER_HOUR_COUNT);

        if (!$isTest) {
            $threshold = new \DateTime();
            $threshold->modify('-1 hour');

            $sentLastHourCount = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
                'status' => 'Sent',
                'sentAt>' => $threshold->format('Y-m-d H:i:s')
            ))->count();

            if ($sentLastHourCount >= $maxBatchSize) {
                return;
            }

            $maxBatchSize = $maxBatchSize - $sentLastHourCount;
        }

        $queueItemList = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
            'status' => 'Pending',
            'massEmailId' => $massEmail->id,
            'isTest' => $isTest
        ))->limit(0, $maxBatchSize)->find();

        $templateId = $massEmail->get('emailTemplateId');
        if (!$templateId) {
            $this->setFailed($massEmail);
            return;
        }

        $campaign = null;
        $campaignId = $massEmail->get('campaignId');
        if ($campaignId) {
            $campaign = $this->getEntityManager()->getEntity('Campaign', $campaignId);
        }
        $emailTemplate = $this->getEntityManager()->getEntity('EmailTemplate', $templateId);
        if (!$emailTemplate) {
            $this->setFailed($massEmail);
            return;
        }
        $attachmentList = $emailTemplate->get('attachments');

        foreach ($queueItemList as $queueItem) {
            $this->sendQueueItem($queueItem, $massEmail, $emailTemplate, $attachmentList, $campaign, $isTest);
        }

        if (!$isTest) {
            $countLeft = $this->getEntityManager()->getRepository('EmailQueueItem')->where(array(
                'status' => 'Pending',
                'massEmailId' => $massEmail->id,
                'isTest' => false
            ))->count();

            if ($countLeft == 0) {
                $massEmail->set('status', 'Complete');
                $this->getEntityManager()->saveEntity($massEmail);
            }
        }
    }

    protected function getPreparedEmail(Entity $queueItem, Entity $massEmail, Entity $emailTemplate, Entity $target, $trackingUrlList = [])
    {
        $templateParams = array(
            'parent' => $target
        );

        $emailData = $this->getEmailTemplateService()->parseTemplate($emailTemplate, $templateParams);

        $body = $emailData['body'];

        $optOutUrl = $this->getConfig()->get('siteUrl') . '?entryPoint=unsubscribe&id=' . $queueItem->id;
        $optOutLink = '<a href="'.$optOutUrl.'">'.$this->getLanguage()->translate('Unsubscribe', 'labels', 'Campaign').'</a>';

        $body = str_replace('{optOutUrl}', $optOutUrl, $body);
        $body = str_replace('{optOutLink}', $optOutLink, $body);

        foreach ($trackingUrlList as $trackingUrl) {
            $url = $this->getConfig()->get('siteUrl') . '?entryPoint=campaignUrl&id=' . $trackingUrl->id . '&queueItemId=' . $queueItem->id;
            $body = str_replace($trackingUrl->get('urlToUse'), $url, $body);
        }

        if (!$this->getConfig()->get('massEmailDisableMandatoryOptOutLink') && stripos($body, '?entryPoint=unsubscribe&id') === false) {
            if ($emailData['isHtml']) {
                $body .= "<br><br>" . $optOutLink;
            } else {
                $body .= "\n\n" . $optOutUrl;
            }
        }

        $trackOpenedUrl = $this->getConfig()->get('siteUrl') . '?entryPoint=campaignTrackOpened&id=' . $queueItem->id;
        $trackOpenedHtml = '<img width="1" height="1" border="0" src="'.$trackOpenedUrl.'">';

        if ($massEmail->get('campaignId')) {
            if ($emailData['isHtml']) {
                if ($massEmail->get('campaignId')) {
                    $body .= $trackOpenedHtml;
                }
            }
        }

        $emailData['body'] = $body;

        $email = $this->getEntityManager()->getEntity('Email');
        $email->set($emailData);

        $emailAddress = $target->get('emailAddress');

        if (empty($emailAddress)) {
            return false;
        }

        $email->set('to', $emailAddress);

        if ($massEmail->get('fromAddress')) {
            $email->set('from', $massEmail->get('fromAddress'));
        }
        if ($massEmail->get('replyToAddress')) {
            $email->set('replyTo', $massEmail->get('replyToAddress'));
        }

        return $email;
    }

    protected function sendQueueItem(Entity $queueItem, Entity $massEmail, Entity $emailTemplate, $attachmentList = [], $campaign = null, $isTest = false)
    {
        $queueItemFetched = $this->getEntityManager()->getEntity($queueItem->getEntityType(), $queueItem->id);
        if ($queueItemFetched->get('status') !== 'Pending') {
            return;
        }

        $queueItem->set('status', 'Sending');
        $this->getEntityManager()->saveEntity($queueItem);

        $target = $this->getEntityManager()->getEntity($queueItem->get('targetType'), $queueItem->get('targetId'));
        if (!$target || !$target->id || !$target->get('emailAddress')) {
            $queueItem->set('status', 'Failed');
            $this->getEntityManager()->saveEntity($queueItem);
            return;
        }

        $emailAddress = $target->get('emailAddress');
        if (!$emailAddress) {
            $queueItem->set('status', 'Failed');
            $this->getEntityManager()->saveEntity($queueItem);
            return false;
        }

        $emailAddressRecord = $this->getEntityManager()->getRepository('EmailAddress')->getByAddress($emailAddress);
        if ($emailAddressRecord) {
            if ($emailAddressRecord->get('invalid') || $emailAddressRecord->get('optOut')) {
                $queueItem->set('status', 'Failed');
                $this->getEntityManager()->saveEntity($queueItem);
                return false;
            }
        }

        $trackingUrlList = [];
        if ($campaign) {
            $trackingUrlList = $campaign->get('trackingUrls');
        }

        $email = $this->getPreparedEmail($queueItem, $massEmail, $emailTemplate, $target, $trackingUrlList);

        $params = array();
        if ($massEmail->get('fromName')) {
            $params['fromName'] = $massEmail->get('fromName');
        }
        if ($massEmail->get('replyToName')) {
            $params['replyToName'] = $massEmail->get('replyToName');
        }

        $isSent = false;

        try {
            $attemptCount = $queueItem->get('attemptCount');
            $attemptCount++;
            $queueItem->set('attemptCount', $attemptCount);

            $message = new \Zend\Mail\Message();

            $header = new \Core\Core\Mail\Mail\Header\XQueueItemId();
            $header->setId($queueItem->id);
            $message->getHeaders()->addHeader($header);

            $message->getHeaders()->addHeaderLine('Precedence', 'bulk');

            if (!$this->getConfig()->get('massEmailDisableMandatoryOptOutLink')) {
                $optOutUrl = $this->getConfig()->getSiteUrl() . '?entryPoint=unsubscribe&id=' . $queueItem->id;
                $message->getHeaders()->addHeaderLine('List-Unsubscribe', '<' . $optOutUrl . '>');
            }

            $this->getMailSender()->useGlobal()->send($email, $params, $message, $attachmentList);

            $isSent = true;
        } catch (\Exception $e) {
            if ($queueItem->get('attemptCount') >= self::MAX_ATTEMPT_COUNT) {
                $queueItem->set('status', 'Failed');
            } else {
                $queueItem->set('status', 'Pending');
            }
            $this->getEntityManager()->saveEntity($queueItem);
            $GLOBALS['log']->error('MassEmail#sendQueueItem: [' . $e->getCode() . '] ' .$e->getMessage());
            return false;
        }

        if ($isSent) {
            $emailObject = $emailTemplate;
            if ($massEmail->get('storeSentEmails') && !$isTest) {
                $this->getEntityManager()->saveEntity($email);
                $emailObject = $email;
            }

            $queueItem->set('emailAddress', $target->get('emailAddress'));

            $queueItem->set('status', 'Sent');
            $queueItem->set('sentAt', date('Y-m-d H:i:s'));
            $this->getEntityManager()->saveEntity($queueItem);

            if ($campaign) {
                $this->getCampaignService()->logSent($campaign->id, $queueItem->id, $target, $emailObject, $target->get('emailAddress'), null, $queueItem->get('isTest'));
            }
        }

        return true;
    }

    protected function getEmailTemplateService()
    {
        if (!$this->emailTemplateService) {
            $this->emailTemplateService = $this->getServiceFactory()->create('EmailTemplate');
        }
        return $this->emailTemplateService;
    }

    protected function getCampaignService()
    {
        if (!$this->campaignService) {
            $this->campaignService = $this->getServiceFactory()->create('Campaign');
        }
        return $this->campaignService;
    }

    protected function findLinkedEntitiesQueueItems($id, $params)
    {
        $link = 'queueItems';

        $entity = $this->getEntityManager()->getEntity('MassEmail', $id);

        $selectParams = $this->getSelectManager('EmailQueueItem')->getSelectParams($params, false);

        if (array_key_exists($link, $this->linkSelectParams)) {
            $selectParams = array_merge($selectParams, $this->linkSelectParams[$link]);
        }

        $selectParams['whereClause'][] = array(
            'isTest' => false
        );

        $collection = $this->getRepository()->findRelated($entity, $link, $selectParams);

        $recordService = $this->getRecordService('EmailQueueItem');

        foreach ($collection as $e) {
            $recordService->loadAdditionalFieldsForList($e);
            $recordService->prepareEntityForOutput($e);
        }

        $total = $this->getRepository()->countRelated($entity, $link, $selectParams);

        return array(
            'total' => $total,
            'collection' => $collection
        );
    }

}

