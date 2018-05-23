<?php


namespace Core\Entities;

class Portal extends \Core\Core\ORM\Entity
{
    protected $settingsAttributeList = [
        'companyLogoId',
        'tabList',
        'quickCreateList',
        'dashboardLayout',
        'dashletsOptions',
        'theme',
        'language',
        'timeZone',
        'dateFormat',
        'timeFormat',
        'weekStart',
        'defaultCurrency'
    ];

    public function getSettingsAttributeList()
    {
        return $this->settingsAttributeList;
    }

}
