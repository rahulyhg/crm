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

namespace Core\Modules\Advanced\Services;

use Core\Core\Exceptions\Error;
use Core\ORM\Entity;

class Workflow extends \Core\Services\Record
{
    protected function init()
    {
        parent::init();

        $this->addDependency('mailSender');
        $this->addDependency('workflowHelper');
        $this->addDependency('container');
        $this->addDependency('config');
        $this->addDependency('crypt');
    }

    protected function getMailSender()
    {
        return $this->getInjection('mailSender');
    }

    protected function getWorkflowHelper()
    {
        return $this->getInjection('workflowHelper');
    }

    protected function getCrypt()
    {
        return $this->injections['crypt'];
    }

    /**
     * Send email defined in workflow
     *
     * @param  array $data  See validateSendEmailData method
     * @return bool
     */
    public function sendEmail(array $data)
    {
        if (!$this->validateSendEmailData($data)) {
            throw new Error('Workflow['.$data['workflowId'].'][sendEmail]: Email data is broken.');
        }

        $entityManager = $this->getEntityManager();

        $workflow = $entityManager->getEntity('Workflow', $data['workflowId']);
        if (!$workflow) return;

        if (!$workflow->get('isActive')) {
            return;
        }

        $entity = $entityManager->getEntity($data['entityName'], $data['entityId']);
        if (!isset($entity)) {
            throw new Error('Workflow['.$data['workflowId'].'][sendEmail]: Entity is not found.');
        }

        $entityService = $this->getServiceFactory()->create($entity->getEntityName());
        $entityService->loadAdditionalFields($entity);

        $toEmail = $this->getEmailAddress($data['to']);
        $fromEmail = $this->getEmailAddress($data['from']);
        if (empty($toEmail) || empty($fromEmail)) {
            throw new Error('Workflow['.$data['workflowId'].'][sendEmail]: Email address is empty.');
        }

        $entityHash = array(
            $data['entityName'] => $entity,
        );

        if (isset($data['to']['entityName']) && isset($data['to']['entityId']) && $data['to']['entityName'] != $data['entityName']) {
            $toEntity = $data['to']['entityName'];
            $entityHash[$toEntity] = $entityManager->getEntity($toEntity, $data['to']['entityId']);
        }

        if (isset($data['from']['entityName']) && $data['from']['entityName'] == 'User') {
            $entityHash['User'] = $entityManager->getEntity('User', $data['from']['entityId']);
            $fromName = $entityHash['User']->get('name');
        }

        $emailTemplateParams = array(
            'entityHash' => $entityHash,
            'emailAddress' => $toEmail,
        );
        if ($entity->hasField('parentId') && $entity->hasField('parentType')) {
            $emailTemplateParams['parentId'] = $entity->get('parentId');
            $emailTemplateParams['parentType'] = $entity->get('parentType');
        }

        $emailTemplateService = $this->getServiceFactory()->create('EmailTemplate');
        $emailTemplate = $emailTemplateService->parse($data['emailTemplateId'], $emailTemplateParams, true);

        $emailData = array(
            'from' => $fromEmail,
            'to' => $toEmail,
            'subject' => $emailTemplate['subject'],
            'body' => $emailTemplate['body'],
            'isHtml' => $emailTemplate['isHtml'],
            'parentId' => $entity->id,
            'parentType' => $entity->getEntityName(),
        );

        if (isset($fromName)) {
            $emailData['fromName'] = $fromName;
        }

        $email = $entityManager->getEntity('Email');
        $email->set($emailData);

        $attachmentList = [];
        if (!empty($emailTemplate['attachmentsIds'])) {
            foreach ($emailTemplate['attachmentsIds'] as $attachmentId) {
                $attachment = $entityManager->getEntity('Attachment', $attachmentId);
                if (isset($attachment)) {
                    $attachmentList[] = $attachment;
                }
            }

            if (!$data['doNotStore']) {
                $email->set('attachmentsIds', $emailTemplate['attachmentsIds']);
            }
        }

        $isSent = false;

        //send the email via user SMTP
        if (isset($data['from']['entityName']) && $data['from']['entityName'] == 'User' && isset($data['from']['entityId'])) {
            $smtpParams = $this->getUserSmtpParams($fromEmail, $data['from']['entityId']);
            if (!empty($smtpParams)) {
                try {
                    $message = null;
                    $this->getMailSender()->useSmtp($smtpParams)->send($email, array(), $message, $attachmentList);
                    $isSent = true;
                } catch (\Exception $e) {
                    $isSent = false;
                }
            }
        }

        $sendExceptionMessage = null;

        if (!$isSent) {
            try {
                $message = null;
                $this->getMailSender()->useGlobal()->send($email, array(), $message, $attachmentList);
            } catch (\Exception $e) {
                $sendExceptionMessage = $e->getMessage();
            }
        }

        if (isset($sendExceptionMessage)) {
            throw new Error('Workflow['.$data['workflowId'].'][sendEmail]: '.$sendExceptionMessage.'.');
        }

        if (!$data['doNotStore']) {
            $entityManager->saveEntity($email);
        }

        return true;
    }

    public function jobTriggerWorkflow(array $data)
    {
        $entityId = $data['entityId'];
        $entityType = $data['entityType'];

        if (empty($data['entityId']) || empty($data['entityType']) || empty($data['nextWorkflowId'])) {
            throw new Error('Workflow['.$data['workflowId'].'][triggerWorkflow]: Not sufficient job data.');
        }

        $entity = $this->getEntityManager()->getEntity($entityType, $entityId);

        if (!$entity) {
            throw new Error('Workflow['.$data['workflowId'].'][triggerWorkflow]: Empty job data.');
        }

        if (is_array($data['values'])) {
            foreach ($data['values'] as $attribute => $value) {
                $entity->setFetched($attribute, $value);
            }
        }

        $this->triggerWorkflow($entity, $data['nextWorkflowId']);

        return true;
    }

    public function triggerWorkflow($entity, $workflowId)
    {
        $workflow = $this->getEntityManager()->getEntity('Workflow', $workflowId);
        if (!$workflow) return;

        if (!$workflow->get('isActive')) {
            return;
        }

        $workflowManager = $this->getInjection('container')->get('workflowManager');

        if ($workflowManager->checkConditions($workflow, $entity)) {
            $workflowLogRecord = $this->getEntityManager()->getEntity('WorkflowLogRecord');
            $workflowLogRecord->set(array(
                'workflowId' => $workflowId,
                'targetId' => $entity->id,
                'targetType' => $entity->getEntityType()
            ));
            $this->getEntityManager()->saveEntity($workflowLogRecord);

            $workflowManager->runActions($workflow, $entity);
        }
    }

    /**
     * Validate sendEmail data
     *
     * @param  array  $data
     * @return bool
     */
    protected function validateSendEmailData(array $data)
    {
        $requiredParams = array(
            'entityId',
            'entityName',
            'emailTemplateId',
            'to',
            'from',
        );

        foreach ($requiredParams as $name) {
            if (!isset($data[$name])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get email address depends on inputs
     * @param  array $data
     * @return string
     */
    protected function getEmailAddress(array $data)
    {
        if (isset($data['email'])) {
            return $data['email'];
        }

        if (isset($data['entityName']) && isset($data['entityId'])) {
            $entity = $this->getEntityManager()->getEntity($data['entityName'], $data['entityId']);
        }

        if (isset($data['type'])) {
            $workflowHelper = $this->getWorkflowHelper();

            switch ($data['type']) {
                case 'specifiedTeams':
                    $userIds = $workflowHelper->getUserIdsByTeamIds($data['entityIds']);
                    return implode('; ', $workflowHelper->getUsersEmailAddress($userIds));
                    break;

                case 'teamUsers':
                    $entity->loadLinkMultipleField('teams');
                    $userIds = $workflowHelper->getUserIdsByTeamIds($entity->get('teamsIds'));
                    return implode('; ', $workflowHelper->getUsersEmailAddress($userIds));
                    break;

                case 'followers':
                    $userIds = $workflowHelper->getFollowerUserIds($entity);
                    return implode('; ', $workflowHelper->getUsersEmailAddress($userIds));
                    break;

                case 'followersExcludingAssignedUser':
                    $userIds = $workflowHelper->getFollowerUserIdsExcludingAssignedUser($entity);
                    return implode('; ', $workflowHelper->getUsersEmailAddress($userIds));
                    break;

                case 'system':
                    return $this->getInjection('config')->get('outboundEmailFromAddress');
                    break;

                case 'specifiedUsers':
                    return implode('; ', $workflowHelper->getUsersEmailAddress($data['entityIds']));
                    break;

                case 'specifiedContacts':
                    return implode('; ', $workflowHelper->getEmailAddressesForEntity('Contact', $data['entityIds']));
                    break;
            }
        }

        if ($entity instanceof Entity && $entity->hasField('emailAddress')) {
            return $entity->get('emailAddress');
        }
    }

    protected function getUserSmtpParams($emailAddress, $userId)
    {
        $smtpParams = null;

        $user = $this->getEntityManager()->getEntity('User', $userId);
        if (!isset($user)) {
            return $smtpParams;
        }

        $userPreferences = $this->getEntityManager()->getEntity('Preferences', $userId);
        if (!isset($userPreferences)) {
            return $smtpParams;
        }

        $smtpParams = $userPreferences->getSmtpParams();

        //getSmtpParamsFromEmailAccount
        if (!$smtpParams) {
            $emailAccount = $this->getEntityManager()->getRepository('EmailAccount')->where([
                'emailAddress' => $emailAddress,
                'assignedUserId' => $userId,
                'active' => true,
                'useSmtp' => true
            ])->findOne();

            if (isset($emailAccount)) {
                $emailAccountSmtpParams = array();
                $emailAccountSmtpParams['server'] = $emailAccount->get('smtpHost');
                if ($emailAccountSmtpParams['server']) {
                    $emailAccountSmtpParams['port'] = $emailAccount->get('smtpPort');
                    $emailAccountSmtpParams['auth'] = $emailAccount->get('smtpAuth');
                    $emailAccountSmtpParams['security'] = $emailAccount->get('smtpSecurity');
                    $emailAccountSmtpParams['username'] = $emailAccount->get('smtpUsername');
                    $emailAccountSmtpParams['password'] = $emailAccount->get('smtpPassword');

                    $smtpParams = $emailAccountSmtpParams;
                }
            }
        }

        if ($smtpParams) {
            if (array_key_exists('password', $smtpParams)) {
                $smtpParams['password'] = $this->getCrypt()->decrypt($smtpParams['password']);
            }
            $smtpParams['fromName'] = $user->get('name');
        }

        return $smtpParams;
    }

    public function runScheduledWorkflow(array $data)
    {
        $entityManager = $this->getEntityManager();

        $workflow = $entityManager->getEntity('Workflow', $data['workflowId']);
        if (!$workflow instanceof Entity) {
            throw new Error('Workflow['.$data['workflowId'].'][runScheduledWorkflow]: Entity is not found.');
        }

        if (!$workflow->get('isActive')) {
            return;
        }

        $targetReport = $workflow->get('targetReport');
        if (!$targetReport instanceof Entity) {
            throw new Error('Workflow['.$data['workflowId'].'][runScheduledWorkflow]: TargetReport Entity is not found.');
        }

        $reportService = $this->getServiceFactory()->create('Report');
        $result = $reportService->run($targetReport->get('id'));

        $jobEntity = $entityManager->getEntity('Job');

        if (isset($result['collection']) && is_object($result['collection'])) {
            foreach ($result['collection'] as $collectionEntity) {
                $job = clone $jobEntity;
                $job->set(array(
                    'serviceName' => 'Workflow',
                    'method' => 'runScheduledWorkflowForEntity',
                    'data' => array(
                        'workflowId' => $workflow->get('id'),
                        'entityName' => $collectionEntity->getEntityName(),
                        'entityId' => $collectionEntity->get('id'),
                    ),
                    'executeTime' => date('Y-m-d H:i:s'),
                ));
                $entityManager->saveEntity($job);
            }
        }
    }

    public function runScheduledWorkflowForEntity(array $data)
    {
        $entityManager = $this->getEntityManager();

        $entity = $entityManager->getEntity($data['entityName'], $data['entityId']);
        if (!$entity instanceof Entity) {
            throw new Error('Workflow['.$data['workflowId'].'][runActions]: Entity['.$data['entityName'].'] ['.$data['entityId'].'] is not found.');
        }

        $this->triggerWorkflow($entity, $data['workflowId']);
    }
}

