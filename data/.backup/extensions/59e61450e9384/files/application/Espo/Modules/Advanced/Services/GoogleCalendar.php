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

use \Core\ORM\Entity;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Forbidden;

class GoogleCalendar extends \Core\Services\Record
{
    protected function init()
    {
        parent::init();
        $this->addDependency('language');
        $this->addDependency('container');
        $this->addDependency('acl');
    }

    protected function getLanguage()
    {
        return $this->injections['language'];
    }

    protected function getAcl()
    {
        return $this->injections['acl'];
    }

    protected function getContainer()
    {
        return $this->injections['container'];
    }

    public function usersCalendars(array $params = null)
    {
        $calendar = new \Core\Modules\Advanced\Core\Google\Actions\Calendar($this->getContainer(), $this->getEntityManager(), $this->getMetadata(), $this->getConfig());

        $calendar->setUserId($this->getUser()->id);

        return $calendar->getCalendarList();
    }

    public function syncCalendar(Entity $calendar)
    {
        $externalAccount = $this->getEntityManager()->getEntity('ExternalAccount', 'Google__' . $calendar->get('userId'));
        $enabled = $externalAccount->get('enabled') && ($externalAccount->get('calendarEnabled') || $externalAccount->get('googleCalendarEnabled'));

        if ($enabled && $calendar->get('userId')) {
            $isConnected = $this->getServiceFactory()->create('ExternalAccount')->ping('Google', $calendar->get('userId'));
            if (! $isConnected) {
                //notify user
                $GLOBALS['log']->error($calendar->get('userName'). ' IS NOT CONNECTED to GC');
                return false;
            }

            $calendarAction = new \Core\Modules\Advanced\Core\Google\Actions\Calendar($this->getContainer(), $this->getEntityManager(), $this->getMetadata(), $this->getConfig());
            $calendarAction->setUserId($calendar->get('userId'));
            $syncResult = $calendarAction->run($calendar, $externalAccount);

            return $syncResult;
        }
        return false;
    }
}
