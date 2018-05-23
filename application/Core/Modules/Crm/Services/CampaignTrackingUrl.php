<?php


namespace Core\Modules\Crm\Services;

use \Core\Core\Exceptions\Forbidden;
use \Core\ORM\Entity;

class CampaignTrackingUrl extends \Core\Services\Record
{
    protected function beforeCreate(Entity $entity, array $data = array())
    {
        parent::beforeCreate($entity, $data);
        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }
    }
}

