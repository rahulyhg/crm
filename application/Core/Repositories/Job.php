<?php


namespace Core\Repositories;

use Core\ORM\Entity;

class Job extends \Core\Core\ORM\Repositories\RDB
{
    protected function init()
    {
        parent::init();
        $this->addDependency('config');
    }

    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    public function beforeSave(Entity $entity, array $options = array())
    {
        if (!$entity->has('executeTime')) {
            $entity->set('executeTime', date('Y-m-d H:i:s'));
        }

        if (!$entity->has('attempts')) {
            $attempts = $this->getConfig()->get('cron.attempts', 0);
            $entity->set('attempts', $attempts);
        }
    }
}

