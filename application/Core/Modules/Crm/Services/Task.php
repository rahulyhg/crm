<?php


namespace Core\Modules\Crm\Services;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;

use \Core\ORM\Entity;

class Task extends \Core\Services\Record
{
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
}
