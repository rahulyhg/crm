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

namespace Core\Modules\Advanced\Hooks\ExternalAccount;

use Core\ORM\Entity;

class Google extends \Core\Core\Hooks\Base
{
    public static $order = 9;

   protected function init()
    {
        parent::init();
        $this->addDependency('serviceFactory');
    }

    protected function getServiceFactory()
    {
        return $this->injections['serviceFactory'];
    }
    
    public function afterSave(Entity $entity)
    {
        list($integration, $userId) = explode('__', $entity->id);
        
        if ($integration == 'Google') {
              
            $storedUsersCalendars = $this->getEntityManager()->getRepository('GoogleCalendar')->storedUsersCalendars($userId);
            $direction = $entity->get('calendarDirection');
            $monitoredCalendarIds = $entity->get('calendarMonitoredCalendarsIds');
            $monitoredCalendars = $entity->get('calendarMonitoredCalendarsNames');
            if (empty($monitoredCalendarIds)) {
                $monitoredCalendarIds = array();
            }
            $mainCalendarId = $entity->get('calendarMainCalendarId');
            $mainCalendarName = $entity->get('calendarMainCalendarName');
                
            if ($direction == "GCToCore" && !in_array($mainCalendarId, $monitoredCalendarIds)) {
                $monitoredCalendarIds[] = $mainCalendarId;
                $monitoredCalendars->$mainCalendarId = $mainCalendarName;
            }
            
            foreach($monitoredCalendarIds as $calendarId) {
                
                $googleCalendar = $this->getEntityManager()->getRepository('GoogleCalendar')->getCalendarByGCId($calendarId, $userId);
                
                if (empty($googleCalendar)) {
                    
                    $googleCalendar = $this->getEntityManager()->getEntity('GoogleCalendar');
                    $googleCalendar->set('name', $monitoredCalendars->$calendarId);
                    $googleCalendar->set('calendarId', $calendarId);
                    $this->getEntityManager()->saveEntity($googleCalendar);
                }
                
                $id = $googleCalendar->id;
                
                if (isset($storedUsersCalendars['monitored'][$id])) {
                
                    if (!$storedUsersCalendars['monitored'][$id]['active']) {
                        
                        $calendarEntity = $this->getEntityManager()->getEntity('GoogleCalendarUser', $storedUsersCalendars['monitored'][$id]['id']);
                        $calendarEntity->set('active', true);
                        $this->getEntityManager()->saveEntity($calendarEntity);
                    
                    }
                
                } else {
                    $calendarEntity = $this->getEntityManager()->getEntity('GoogleCalendarUser');
                    $calendarEntity->set('userId', $userId);
                    $calendarEntity->set('type', 'monitored');
                    $calendarEntity->set('role', 'owner');
                    $calendarEntity->set('googleCalendarId', $id);
                    $this->getEntityManager()->saveEntity($calendarEntity);
                }
            }
            
            foreach($storedUsersCalendars['monitored'] as $id => $calendar) {
                if ($calendar['active'] && (!is_array($monitoredCalendarIds) || !in_array($calendar['calendar_id'], $monitoredCalendarIds))) {
                    $calendarEntity = $this->getEntityManager()->getEntity('GoogleCalendarUser', $calendar['id']);
                    $calendarEntity->set('active', false);
                    $this->getEntityManager()->saveEntity($calendarEntity);
                }
            }
            
            if ($direction == "GCToCore") {
                $mainCalendarId = '';
                $mainCalendarName = array();
            }
            
            if (empty($mainCalendarId)) {
                foreach($storedUsersCalendars['main'] as $calendarId => $calendar) {
                    if ($calendar['active']) {
                        $calendarEntity = $this->getEntityManager()->getEntity('GoogleCalendarUser', $calendar['id']);
                        $calendarEntity->set('active', false);
                        $this->getEntityManager()->saveEntity($calendarEntity);
                    } 
                }
            } else {
                $googleCalendar = $this->getEntityManager()->getRepository('GoogleCalendar')->getCalendarByGCId($mainCalendarId, $userId);
                    
                if (empty($googleCalendar)) {
                    
                    $googleCalendar = $this->getEntityManager()->getEntity('GoogleCalendar');
                    $googleCalendar->set('name', $mainCalendarName);
                    $googleCalendar->set('calendarId', $mainCalendarId);
                    $this->getEntityManager()->saveEntity($googleCalendar);
                }
                
                $id = $googleCalendar->id;
                
                foreach($storedUsersCalendars['main'] as $calendarId => $calendar) {
                    
                    if ($calendar['active'] && $id != $calendarId) {
                        
                        $calendarEntity = $this->getEntityManager()->getEntity('GoogleCalendarUser', $calendar['id']);
                        $calendarEntity->set('active', false);
                        $this->getEntityManager()->saveEntity($calendarEntity);
                    
                    } else if (!$calendar['active'] && $id == $calendarId) {
                        
                        $calendarEntity = $this->getEntityManager()->getEntity('GoogleCalendarUser', $calendar['id']);
                        $calendarEntity->set('active', true);
                        $this->getEntityManager()->saveEntity($calendarEntity);
                    
                    }
                    
                }
                    
                if (!isset($storedUsersCalendars['main'][$id])) {
                   
                    $calendarEntity = $this->getEntityManager()->getEntity('GoogleCalendarUser');
                    $calendarEntity->set('userId', $userId);
                    $calendarEntity->set('type', 'main');
                    $calendarEntity->set('role', 'owner');
                    $calendarEntity->set('googleCalendarId', $id);
                    $this->getEntityManager()->saveEntity($calendarEntity);
                
                }
            }
        }
    }

    public function beforeSave(Entity $entity)
    {
        list($integration, $userId) = explode('__', $entity->id);
        
        if ($integration == 'Google') {
           /* 
            if ($entity->get('enabled') && $entity->get('googleContactsEnabled')) {
                $userEmail = $this->getServiceFactory()->create('GoogleContacts')->getUserEmail($userId);
                //$entity->set('googleAccountEmail', $userEmail);
                
                if ($userEmail) {
                    $existAccount = $this->getEntityManager()->getRepository('GoogleAccount')->where(['name' => $userEmail])->findOne();
                    if (!$existAccount) {
                        $googleAccount = $this->getEntityManager()->getEntity('GoogleAccount');
                        $googleAccount->set('name', $userEmail);
                        $this->getEntityManager()->saveEntity($googleAccount);
                    }
                }
            } else {
               // $entity->set('googleAccountEmail', null);
            }
            */
            $prevEntity = $this->getEntityManager()->getEntity('ExternalAccount', $entity->id);
            
            if ($prevEntity && $prevEntity->get('calendarStartDate') > $entity->get('calendarStartDate')) {
                $googleCalendarUsers = $this->getEntityManager()->getRepository('GoogleCalendarUser')
                    ->where(array(
                        'active' => true, 
                        'userId' => $userId))
                    ->find();
                
                foreach ($googleCalendarUsers as $googleCalendarUser) {
                    $googleCalendarUser->set('pageToken', '');
                    $googleCalendarUser->set('syncToken', '');
                    $googleCalendarUser->set('lastSync', null);
                    $this->getEntityManager()->saveEntity($googleCalendarUser);
                }
            } 
        }
    }
}
