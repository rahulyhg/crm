<?php


namespace Core\Modules\Crm\Services;

use \Core\ORM\Entity;
use \Core\Modules\Crm\Business\Event\Invitations;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;

class Meeting extends \Core\Services\Record
{
    protected function init()
    {
        $this->addDependencyList([
            'preferences',
            'language',
            'dateTime',
            'container',
            'fileManager',
            'number'
        ]);
    }

    protected $exportSkipFieldList = ['duration'];

    protected function getMailSender()
    {
        return $this->getInjection('container')->get('mailSender');
    }

    protected function getPreferences()
    {
        return $this->getInjection('preferences');
    }

    protected function getCrypt()
    {
        return $this->getInjection('container')->get('crypt');
    }

    protected function getLanguage()
    {
        return $this->getInjection('language');
    }

    protected function getDateTime()
    {
        return $this->getInjection('dateTime');
    }

    public function checkAssignment(Entity $entity)
    {
        $result = parent::checkAssignment($entity);
        if (!$result) return false;

        $userIdList = $entity->get('usersIds');
        if (!is_array($userIdList)) {
            $userIdList = [];
        }

        $newIdList = [];
        if (!$entity->isNew()) {
            $existingIdList = [];
            foreach ($entity->get('users') as $user) {
                $existingIdList[] = $user->id;
            }
            foreach ($userIdList as $id) {
                if (!in_array($id, $existingIdList)) {
                    $newIdList[] = $id;
                }
            }
        } else {
            $newIdList = $userIdList;
        }

        foreach ($newIdList as $userId) {
            if (!$this->getAcl()->checkAssignmentPermission($userId)) {
                return false;
            }
        }

        return true;
    }

    protected function getInvitationManager()
    {
        $smtpParams = $this->getPreferences()->getSmtpParams();
        if ($smtpParams) {
            if (array_key_exists('password', $smtpParams)) {
                $smtpParams['password'] = $this->getCrypt()->decrypt($smtpParams['password']);
            }
            $smtpParams['fromAddress'] = $this->getUser()->get('emailAddress');
            $smtpParams['fromName'] = $this->getUser()->get('name');
        }
        return new Invitations(
            $this->getEntityManager(),
            $smtpParams,
            $this->getMailSender(),
            $this->getConfig(),
            $this->getInjection('fileManager'),
            $this->getDateTime(),
            $this->getInjection('number'),
            $this->getLanguage()
        );
    }

    public function sendInvitations(Entity $entity)
    {
        $invitationManager = $this->getInvitationManager();

        $emailHash = array();

        $sentCount = 0;

        $users = $entity->get('users');
        foreach ($users as $user) {
            if ($user->id === $this->getUser()->id) {
                if ($entity->getLinkMultipleColumn('users', 'status', $user->id) === 'Accepted') {
                    continue;
                }
            }
            if ($user->get('emailAddress') && !array_key_exists($user->get('emailAddress'), $emailHash)) {
                $invitationManager->sendInvitation($entity, $user, 'users');
                $emailHash[$user->get('emailAddress')] = true;
                $sentCount ++;
            }
        }

        $contacts = $entity->get('contacts');
        foreach ($contacts as $contact) {
            if ($contact->get('emailAddress') && !array_key_exists($contact->get('emailAddress'), $emailHash)) {
                $invitationManager->sendInvitation($entity, $contact, 'contacts');
                $emailHash[$user->get('emailAddress')] = true;
                $sentCount ++;
            }
        }

        $leads = $entity->get('leads');
        foreach ($leads as $lead) {
            if ($lead->get('emailAddress') && !array_key_exists($lead->get('emailAddress'), $emailHash)) {
                $invitationManager->sendInvitation($entity, $lead, 'leads');
                $emailHash[$user->get('emailAddress')] = true;
                $sentCount ++;
            }
        }

        if (!$sentCount) return false;

        return true;
    }

    public function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);
        $this->loadRemindersField($entity);
    }

    protected function loadRemindersField(Entity $entity)
    {
        $reminders = $this->getRepository()->getEntityReminderList($entity);
        $entity->set('reminders', $reminders);
    }

    public function massSetHeld(array $ids)
    {
        foreach ($ids as $id) {
            $entity = $this->getEntityManager()->getEntity($this->entityType, $id);
            if ($entity && $this->getAcl()->check($entity, 'edit')) {
                $entity->set('status', 'Held');
                $this->getEntityManager()->saveEntity($entity);
            }
        }
        return true;
    }

    public function massSetNotHeld(array $ids)
    {
        foreach ($ids as $id) {
            $entity = $this->getEntityManager()->getEntity($this->entityType, $id);
            if ($entity && $this->getAcl()->check($entity, 'edit')) {
                $entity->set('status', 'Not Held');
                $this->getEntityManager()->saveEntity($entity);
            }
        }
        return true;
    }

}

