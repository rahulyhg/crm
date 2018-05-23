<?php


namespace Core\Modules\Crm\Business\Reminder;

use \Core\ORM\Entity;

class EmailReminder
{
    protected $entityManager;

    protected $mailSender;

    protected $config;

    protected $dateTime;

    protected $templateFileManager;

    protected $language;

    public function __construct($entityManager, $templateFileManager, $mailSender, $config, $fileManager, $dateTime, $number, $language)
    {
        $this->entityManager = $entityManager;
        $this->mailSender = $mailSender;
        $this->config = $config;
        $this->dateTime = $dateTime;
        $this->language = $language;
        $this->number = $number;
        $this->fileManager = $fileManager;
        $this->templateFileManager = $templateFileManager;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getTemplateFileManager()
    {
        return $this->templateFileManager;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getLanguage()
    {
        return $this->language;
    }

    protected function parseInvitationTemplate($contents, $entity, $user = null)
    {
        $contents = str_replace('{eventType}', strtolower($this->language->translate($entity->getEntityName(), 'scopeNames')), $contents);

        $preferences = $this->getEntityManager()->getEntity('Preferences', $user->id);
        $timezone = $preferences->get('timeZone');

        foreach ($entity->getFields() as $field => $d) {
            if (empty($d['type'])) continue;
            $key = '{'.$field.'}';
            switch ($d['type']) {
                case 'datetime':
                    $contents = str_replace($key, $this->dateTime->convertSystemDateTime($entity->get($field), $timezone), $contents);
                    break;
                case 'date':
                    $contents = str_replace($key, $this->dateTime->convertSystemDate($entity->get($field)), $contents);
                    break;
                default:
                    $contents = str_replace($key, $entity->get($field), $contents);
            }
        }

        if ($user) {
            $contents = str_replace('{userName}', $user->get('name'), $contents);
        }

        $siteUrl = rtrim($this->config->get('siteUrl'), '/');

        $url = $siteUrl . '/#' . $entity->getEntityName() . '/view/' . $entity->id;
        $contents = str_replace('{url}', $url, $contents);

        return $contents;
    }

    public function send(Entity $reminder)
    {
        $user = $this->getEntityManager()->getEntity('User', $reminder->get('userId'));
        $entity = $this->getEntityManager()->getEntity($reminder->get('entityType'), $reminder->get('entityId'));

        $emailAddress = $user->get('emailAddress');

        if (empty($user) || empty($emailAddress) || empty($entity)) {
            return;
        }

        $email = $this->getEntityManager()->getEntity('Email');
        $email->set('to', $emailAddress);

        $subjectTpl = $this->getTemplateFileManager()->getTemplate('reminder', 'subject', $entity->getEntityType(), 'Crm');
        $bodyTpl = $this->getTemplateFileManager()->getTemplate('reminder', 'body', $entity->getEntityType(), 'Crm');

        $subjectTpl = str_replace(array("\n", "\r"), '', $subjectTpl);

        $data = array();

        $siteUrl = rtrim($this->getConfig()->get('siteUrl'), '/');
        $recordUrl = $siteUrl . '/#' . $entity->getEntityType() . '/view/' . $entity->id;
        $data['recordUrl'] = $recordUrl;

        $data['entityType'] = $this->getLanguage()->translate($entity->getEntityType(), 'scopeNames');
        $data['entityTypeLowerFirst'] = lcfirst($data['entityType']);

        if ($user) {
            $data['userName'] = $user->get('name');
        }

        $preferences = $this->getEntityManager()->getEntity('Preferences', $user->id);
        $timezone = $preferences->get('timeZone');
        $dateTime = clone($this->dateTime);
        if ($timezone) {
            $dateTime->setTimezone($timezone);
        }

        $htmlizer = new \Core\Core\Htmlizer\Htmlizer($this->fileManager, $dateTime, $this->number, null);

        $subject = $htmlizer->render($entity, $subjectTpl, 'reminder-email-subject-' . $entity->getEntityType(), $data, true);
        $body = $htmlizer->render($entity, $bodyTpl, 'reminder-email-body-' . $entity->getEntityType(), $data, true);

        $email->set('subject', $subject);
        $email->set('body', $body);
        $email->set('isHtml', true);

        $emailSender = $this->mailSender;

        $emailSender->send($email);
    }
}

