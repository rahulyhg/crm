<?php


namespace Core\Modules\Crm\Entities;

class CampaignTrackingUrl extends \Core\Core\ORM\Entity
{
    protected function _getUrlToUse()
    {
        return '{trackingUrl:' . $this->id . '}';
    }

    protected function _hasUrlToUse()
    {
        return !$this->isNew();
    }
}
