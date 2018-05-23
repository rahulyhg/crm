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

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\NotFound;

use \Core\Modules\Advanced\Business\Report\EmailBuilder;

class ReportSending extends \Core\Core\Services\Base
{
    const LIST_REPORT_MAX_SIZE = 1000;

    protected function init()
    {
        parent::init();

        $this->addDependency('entityManager');
        $this->addDependency('serviceFactory');
        $this->addDependency('user');
        $this->addDependency('metadata');
        $this->addDependency('config');
        $this->addDependency('language');
        $this->addDependency('mailSender');
        $this->addDependency('preferences');
        $this->addDependency('fileManager');
        $this->addDependency('templateFileManager');
        $this->addDependency('injectableFactory');
        $this->addDependency('dateTime');
        $this->addDependency('number');
        $this->addDependency('selectManagerFactory');

    }

    protected function getPDO()
    {
        return $this->getEntityManager()->getPDO();
    }

    protected function getLanguage()
    {
        return $this->injections['language'];
    }

    protected function getServiceFactory()
    {
        return $this->injections['serviceFactory'];
    }

    protected function getEntityManager()
    {
        return $this->injections['entityManager'];
    }

    protected function getUser()
    {
        return $this->injections['user'];
    }

    protected function getConfig()
    {
        return $this->injections['config'];
    }

    protected function getMetadata()
    {
        return $this->injections['metadata'];
    }

    protected function getMailSender()
    {
        return $this->injections['mailSender'];
    }

    protected function getPreferences()
    {
        return $this->injections['preferences'];
    }

    protected function getTemplateFileManager()
    {
        return $this->getInjection('templateFileManager');
    }

    protected function getHtmlizer()
    {
        if (empty($this->htmlizer)) {
            $this->htmlizer = new \Core\Core\Htmlizer\Htmlizer($this->getInjection('fileManager'), $this->getInjection('dateTime'), $this->getInjection('number'), null);
        }
        return $this->htmlizer;
    }

    protected function getReportEmailHelper()
    {
        $smtpParams = $this->getPreferences()->getSmtpParams();

        return new EmailBuilder($this->getMetadata(), $this->getEntityManager(), $smtpParams, $this->getMailSender(),$this->getConfig(), $this->getLanguage(), $this->getHtmlizer(), $this->getTemplateFileManager());
    }

    protected function getRecordService($name)
    {
        if ($this->getServiceFactory()->checkExists($name)) {
            $service = $this->getServiceFactory()->create($name);
            $service->setEntityType($name);
        } else {
            $service = $this->getServiceFactory()->create('Record');
            if (method_exists($service, 'setEntityType')) {
                $service->setEntityType($name);
            } else {
                $service->setEntityName($name);
            }
        }

        return $service;
    }

    public function getEmailAttributes($id, $where = null)
    {
        $service = $this->getServiceFactory()->create('Report');
        $report = $this->getEntityManager()->getEntity('Report', $id);
        if (empty($report)) {
            throw new NotFound();
        }
        $params = array();

        if ($report->get('type') == 'List') {
            $params = array(
                'offset' => 0,
                'maxSize' => self::LIST_REPORT_MAX_SIZE
            );
            $orderByList = $report->get('orderByList');
            if ($orderByList) {
                $arr = explode(':', $orderByList);
                $params['sortBy'] = $arr[1];
                $params['asc'] = $arr[0] === 'ASC';
            }
        }
        $data = array(
            'userId' => $this->getUser()->id
        );

        $result = $service->run($id, $where, $params);
        $reportResult = (isset($result['collection']) && is_object($result['collection'])) ? $result['collection']->toArray() : $result;

        $sender = $this->getReportEmailHelper();
        $sender->buildEmailData($data, $reportResult, $report);
        $attachmentId = $this->getExportAttachmentId($report, $result, $where);
        if ($attachmentId) {
            $data['attachmentId'] = $attachmentId;

            $attachment = $this->getEntityManager()->getEntity('Attachment', $attachmentId);
            if ($attachment) {
                $attachment->set(array(
                    'role' => 'Attachment',
                    'parentType' => 'Email'
                ));
                $this->getEntityManager()->saveEntity($attachment);
            }
        }

        $userIdList = $report->getLinkMultipleIdList('emailSendingUsers');

        $nameHash = (object) [];
        $to = '';
        $toArr = [];
        if ($report->get('emailSendingInterval') && count($userIdList)) {
            $userList = $this->getEntityManager()->getRepository('User')->where(array('id' => $userIdList))->find();
            foreach ($userList as $user) {
                $emailAddress = $user->get('emailAddress');
                if ($emailAddress) {
                    $toArr[] = $emailAddress;
                    $nameHash->$emailAddress = $user->get('name');
                }
            }
        }

        $attributes = array(
            'isHtml' => true,
            'body' => $data['emailBody'],
            'name' => $data['emailSubject'],
            'nameHash' => $nameHash,
            'to' => implode(';', $toArr),
        );

        if (!empty($data['attachmentId'])) {
            $attributes['attachmentsIds'] = [$data['attachmentId']];
            $attachment = $this->getEntityManager()->getEntity('Attachment', $data['attachmentId']);
            if ($attachment) {
                $attributes['attachmentsNames'] = array(
                    $data['attachmentId'] => $attachment->get('name')
                );
            }
        }

        return $attributes;
    }

    public function sendReport($data)
    {
        try {
            if (!is_array($data) || !isset($data['userId']) || !isset($data['reportId'])) {
                $GLOBALS['log']->error('Report Sending: Not enough data for sending email. ' . print_r($data, true));
                return false;
            }
            $smtpParams = $this->getPreferences()->getSmtpParams();
            $service = $this->getServiceFactory()->create('Report');
            $report = $this->getEntityManager()->getEntity('Report', $data['reportId']);
            if (empty($report)) {
                $GLOBALS['log']->error('Report Sending: No Report ' . $data['reportId']);
                return false;
            }

            $user = $this->getEntityManager()->getEntity('User', $data['userId']);
            if (empty($user)) {
                $GLOBALS['log']->error('Report Sending: No user with id ' . $data['userId']);
                return false;
            }

            $params = array();

            if ($report->get('type') == 'List') {
                $params = array(
                    'offset' => 0,
                    'maxSize' => 500
                );
                $orderByList = $report->get('orderByList');
                if ($orderByList) {
                    $arr = explode(':', $orderByList);
                    $params['sortBy'] = $arr[1];
                    $params['asc'] = $arr[0] === 'ASC';
                }
            }
            $result = $service->run($data['reportId'], [], $params);
            $reportResult = (isset($result['collection']) && is_object($result['collection'])) ? $result['collection']->toArray() : $result;

            if (count($reportResult) == 0 && $report->get('emailSendingDoNotSendEmptyReport')) {
                $GLOBALS['log']->info('Report Sending: Report ' . $report->get('name') . ' is empty and was not send');
                return false;
            }
            $sender = $this->getReportEmailHelper();
            $sender->buildEmailData($data, $reportResult, $report);
            $attachmentId = $this->getExportAttachmentId($report, $result);
            if ($attachmentId) {
                $data['attachmentId'] = $attachmentId;
            }
            $sender->sendEmail($data);
        } catch (\Exception $e) {
            $GLOBALS['log']->error('Report Sending: ' . $e->getMessage());
        }
        return true;
    }

    protected function getExportAttachmentId($report, $resultData, $where = null)
    {
        $entityType = $report->get('entityType');
        $targetEntityService = $this->getRecordService($entityType);
        if (!method_exists($targetEntityService, 'exportCollection')) {
            return false;
        }

        if ($report->get('type') === 'List') {
            if (!array_key_exists('collection', $resultData)) {
                return false;
            }

            $exportParams = array(
                'fieldList' => $report->get('columns'),
                'format' => 'xlsx',
                'exportName' => $report->get('name'),
                'fileName' => $report->get('name') . ' ' . date('Y-m-d')
            );
            try {
                return $targetEntityService->exportCollection($exportParams, $resultData['collection']);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('Report export fail: ' . $e->getMessage());
                return false;
            }
        } else {
            $name = $report->get('name');
            $name = preg_replace("/([^\w\s\d\-_~,;:\[\]\(\).])/u", '_', $name) . ' ' . date('Y-m-d');
            $mimeType = $this->getMetadata()->get(['app', 'export', 'formatDefs', 'xlsx', 'mimeType']);
            $fileExtension = $this->getMetadata()->get(['app', 'export', 'formatDefs', 'xlsx', 'fileExtension']);
            $fileName = $name . '.' . $fileExtension;

            try {
                $service = $this->getServiceFactory()->create('Report');
                $contents = $service->getGridReportXlsx($report->id, $where);

                $attachment = $this->getEntityManager()->getEntity('Attachment');
                $attachment->set(array(
                    'name' => $fileName,
                    'type' => $mimeType,
                    'contents' => $contents,
                    'role' => 'Attachment',
                    'parentType' => 'Email'
                ));
                $this->getEntityManager()->saveEntity($attachment);

                return $attachment->id;
            } catch (\Exception $e) {
                $GLOBALS['log']->error('Report export fail: ' . $e->getMessage());
                return false;
            }
        }
    }

    public function scheduleEmailSending()
    {
        $query = "SELECT id, email_sending_interval AS sendInterval, email_sending_last_date_sent AS lastDateSent, email_sending_time AS sendingTime, email_sending_setting_month AS month, email_sending_setting_day AS day, email_sending_setting_weekdays as weekdays
                FROM report
                WHERE email_sending_interval IS NOT NULL AND email_sending_interval <> ''";

        $sth = $this->getPDO()->prepare($query);
        $sth->execute();
        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $utcTZ = new \DateTimeZone('UTC');
        $now = new \DateTime("now", $utcTZ);

        $defaultTz = $this->getConfig()->get('timeZone');
        $espoTimeZone = new \DateTimeZone($defaultTz);
        foreach ($result as $row) {
            $scheduleSending = false;
            $check = false;

            $lastSent = '';
            if (!empty($row['lastDateSent'])) {
                $lastSent = new \DateTime($row['lastDateSent'], $utcTZ);
                $lastSent->setTimezone($espoTimeZone);
            }
            $nowCopy = clone $now;
            $nowCopy->setTimezone($espoTimeZone);

            switch ($row['sendInterval']) {
                case 'Daily':
                    $check = true;
                    break;
                case 'Weekly':
                    $check = (strpos($row['weekdays'], $nowCopy->format('w')) !== false);
                    break;
                case 'Monthly':
                    $check =
                        $nowCopy->format('j') == $row['day'] ||
                        $nowCopy->format('j') == $nowCopy->format('t') && $nowCopy->format('t') < $row['day'];
                    break;
                case 'Yearly':
                    $check =
                        (
                            $nowCopy->format('j') == $row['day'] ||
                            $nowCopy->format('j') == $nowCopy->format('t') && $nowCopy->format('t') < $row['day']
                        ) &&
                        $nowCopy->format('n') == $row['month'];
                    break;
            }
            if ($check) {
                if (empty($lastSent)) {
                    $scheduleSending = true;
                } else {
                    $nowCopy->setTime(0,0,0);
                    $lastSent->setTime(0,0,0);
                    $diff = $lastSent->diff($nowCopy);
                    if (!empty($diff)) {
                        $dayDiff = (int) ((($diff->invert) ? '-' : '') . $diff->days);
                        if ($dayDiff > 0) {
                            $scheduleSending = true;
                        }
                    }
                }
            }
            if ($scheduleSending) {
                $report = $this->getEntityManager()->getEntity('Report', $row['id']);
                if (empty($report)) {
                    continue;
                }
                $report->loadLinkMultipleField('emailSendingUsers');
                $users = $report->get('emailSendingUsersIds');
                if (empty($users)) {
                    continue;
                }

                $executeTime = clone $now;

                if (!empty($row['sendingTime'])) {
                    $time = explode(':', $row['sendingTime']);

                    if (empty($time[0]) || $time[0] < 0 && $time[0] > 23) {
                        $time[0] = 0;
                    }
                    if (empty($time[1]) || $time[1] < 0 && $time[1] > 59) {
                        $time[1] = 0;
                    }

                    $executeTime->setTimezone($espoTimeZone);
                    $executeTime->setTime($time[0], $time[1], 0);
                    $executeTime->setTimezone($utcTZ);
                }

                $report->set('emailSendingLastDateSent', $executeTime->format('Y-m-d H:i:s'));
                $this->getEntityManager()->saveEntity($report);

                $emailManager = $this->getReportEmailHelper();
                foreach ($users as $userId) {
                    $jobEntity = $this->getEntityManager()->getEntity('Job');

                    $data = array(
                        'userId' => $userId,
                        'reportId' => $report->id
                    );

                    $jobEntity->set(array(
                        'name' => '',
                        'executeTime' => $executeTime->format('Y-m-d H:i:s'),
                        'method' => 'sendReport',
                        'data' => json_encode($data),
                        'serviceName' => 'ReportSending'
                    ));

                    $jobEntityId = $this->getEntityManager()->saveEntity($jobEntity);
                }
            }
        }
    }
}
