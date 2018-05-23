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

namespace Core\Modules\Advanced\Core\Google\Actions;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\NotFound;

class Calendar extends Base
{

    const MAX_EVENT_COUNT = 20;
    const MAX_ESPO_EVENT_INSERT_COUNT = 20;
    const MAX_ESPO_EVENT_UPDATE_COUNT = 20;
    const MAX_RECURRENT_EVENT_COUNT = 20;

    const SUCCESS_INCREMENT = 1;
    const FAIL_INCREMENT = 0.01;

    private $syncParams = array();
    private $eventManager;

    private $eventCounter = 0;
    private $recurrentEventCounter = 0;
    private $espoEventInsertCounter = 0;
    private $espoEventUpdateCounter = 0;

    protected function getClient()
    {
        return parent::getClient()->getCalendarClient();
    }

    public function getCalendarList($params = array())
    {
        static $lists = array();
        $client = $this->getClient();
        $response = $client->getCalendarList($params);

        if (is_array($response) && isset($response['items'])) {
            foreach ($response['items'] as $item) {
                $lists[$item['id']] = $item['summary'];
            }

            if (isset($response['nextPageToken'])) {
                $params['pageToken'] = $response['nextPageToken'];
                $this->getCalendarList($params);
            }
         }
         return $lists;
    }

    private function resetCounters()
    {
        $this->eventCounter = 0;
        $this->recurrentEventCounter = 0;
        $this->espoEventInsertCounter = 0;
        $this->espoEventUpdateCounter = 0;
    }

    private function prepareData($calendar, $externalAccount)
    {
        $this->resetCounters();
        $integrationStartDate = $externalAccount->get('calendarStartDate');
        $lastSync = $calendar->get('lastSync');
        $lastSyncArr = explode('_',  $lastSync);
        $lastSyncTime = (isset($lastSyncArr[0])) ? $lastSyncArr[0] : '';
        $lastSyncId = (isset($lastSyncArr[1])) ? $lastSyncArr[1] : '';
        $startDate = (!empty($lastSyncTime) && $lastSyncTime > $integrationStartDate) ? $lastSyncTime : $integrationStartDate;

        $tz = new \DateTimeZone('UTC');
        $startSyncTime = new \DateTime('now', $tz);

        $entityLabels = array();
        $syncEntitiesTMP = $externalAccount->get('calendarEntityTypes');
        $syncEntities = array();
        if (is_array($syncEntitiesTMP)) {
            foreach ($syncEntitiesTMP as $syncEntity) {
                if ($this->getAcl()->check($syncEntity, 'read')) {
                    $syncEntities[] = $syncEntity;
                }
            }
        }
        if (empty($syncEntities)) {
            throw new Error("No Allowed Entity Choosen");
        }

        foreach ($syncEntities as $entity) {

            $label = $externalAccount->get($entity . "IdentificationLabel");

            if (empty($label)) {
                $entityLabels[$entity] = $label;
            } else {
                $entityLabels = array($entity => $label) + $entityLabels;
            }
        }

        $googleCalendar = $calendar->get('googleCalendar');

        if (!empty($googleCalendar)) {
            $googleCalendarId = $googleCalendar->get('calendarId');
        } else {
            throw new Error("Cannot load calendar {$calendar->get('googleCalendar')} for user {$calendar->get('userId')}");
        }

        $googleCalendarId = (!empty($googleCalendar)) ? $googleCalendar->get('calendarId') : '';
        $isMain = ($calendar->get('type') == 'main');

        $isInMain = (!$isMain && $externalAccount->get('calendarMainCalendarId') == $googleCalendarId);

        $calendarInfo = $this->getClient()->getCalendarInfo($googleCalendarId);
        $googleTimeZone = (!empty($calendarInfo) && isset($calendarInfo['timeZone'])) ? $calendarInfo['timeZone'] : 'UTC';

        $userPreference = $this->getEntityManager()->getEntity('Preferences', $calendar->get('userId'));
        $userTimeZone = $userPreference->get('timeZone');

        if (!$googleTimeZone) {
            $googleTimeZone = 'UTC';
        }

        if (!$userTimeZone) {
            $userTimeZone = 'UTC';
        }
        $defaultEntity = (in_array($externalAccount->get('calendarDefaultEntity'), $syncEntities)) ? $externalAccount->get('calendarDefaultEntity') : '';

        $this->syncParams = array(
            'fetchSince' => $integrationStartDate,
            'startDate' => $startDate,
            'lastUpdatedId' => $lastSyncId,
            'syncEntities' => $syncEntities,
            'entityLabels' => $entityLabels,
            'userId' => $calendar->get('userId'),
            'googleCalendarId' => $googleCalendarId,
            'direction' => $externalAccount->get('calendarDirection'),
            'defaultEntity' => $defaultEntity,
            'isMain' => $isMain,
            'isInMain' => $isInMain,
            'calendar' => $calendar,
            'startSyncTime' => $startSyncTime->format('Y-m-d H:i:s'),
            'googleTimeZone' => $googleTimeZone,
            'userTimeZone' => $userTimeZone,
            'removeGCEventIfRemovedInCore' => $externalAccount->get('removeGoogleCalendarEventIfRemovedInCore'),
            'dontSyncEventAttendees' => $externalAccount->get('dontSyncEventAttendees')

        );
        $this->eventManager = new Event($this->getContainer(), $this->getEntityManager(), $this->getMetadata(), $this->getConfig());
        $this->eventManager->setUserId($this->getUserId());
        $this->eventManager->setCalendarId($googleCalendarId);

        $this->eventManager->syncParams = $this->syncParams;
    }

    private function insertNewCoreEventsIntoGoogle()
    {
        $collection = $this->getEntityManager()->getRepository('GoogleCalendar')->getNewEvents(
            $this->syncParams['userId'],
            $this->syncParams['syncEntities'],
            $this->syncParams['fetchSince'],
            self::MAX_ESPO_EVENT_INSERT_COUNT
        );

        foreach ($collection as $espoEvent) {
            $insertResult = $this->eventManager->insertIntoGoogle($espoEvent);
            $this->espoEventInsertCounter += (($insertResult) ? self::SUCCESS_INCREMENT : self::FAIL_INCREMENT);
        }

        if (count($collection) > 0 && $this->espoEventInsertCounter < self::MAX_ESPO_EVENT_INSERT_COUNT) {
            $this->insertNewCoreEventsIntoGoogle();
        }
        return true;
    }

    private function updateCoreEventsInGoogle($withCompare = false)
    {
        $collection = $this->getEntityManager()->getRepository('GoogleCalendar')->getEvents(
            $this->syncParams['userId'],
            $this->syncParams['syncEntities'],
            $this->syncParams['startDate'],
            $this->syncParams['startSyncTime'],
            $this->syncParams['lastUpdatedId'],
            $this->syncParams['googleCalendarId'],
            self::MAX_ESPO_EVENT_UPDATE_COUNT
        );

        $lastDate = '';

        foreach ($collection as $espoEvent) {
            $updateResult = $this->eventManager->updateGoogleEvent($espoEvent, $withCompare);
            $this->espoEventUpdateCounter += (($updateResult) ? self::SUCCESS_INCREMENT : self::FAIL_INCREMENT);
            $lastDate = (!empty($espoEvent['modifiedAt'])) ? $espoEvent['modifiedAt'] : $espoEvent['createdAt'];
            $id = $espoEvent['id'];
        }

        if (!empty($lastDate)) {
            $this->syncParams['calendar']->set('lastSync', $lastDate . '_' . $id);
            try {
                $this->getEntityManager()->saveEntity($this->syncParams['calendar']);
            } catch (\Exception $e) {
                $GLOBALS['log']->error("GoogleCalendarERROR: Updating lastSync is failed. ({$lastDate})");
            }
        }

        if (count($collection) == self::MAX_ESPO_EVENT_UPDATE_COUNT && $this->espoEventUpdateCounter < self::MAX_ESPO_EVENT_UPDATE_COUNT) {
            $this->updateCoreEventsInGoogle($withCompare);
        }
        return true;
    }

    private function loadGoogleEvents($withCompare = false)
    {
        $syncToken = $this->syncParams['calendar']->get('syncToken');
        $pageToken = $this->syncParams['calendar']->get('pageToken');
        $params = array();

        if (!empty($syncToken)) {
            $params['syncToken'] = $syncToken;
        }

        if (!empty($pageToken)) {
            $params['pageToken'] = $pageToken;
        }

        $result = $this->eventManager->getEventList($params);

        if (empty($result) || !is_array($result)) {
            return false;
        }

        if (isset($result['success']) && $result['success'] === false) {
            if (isset($result['action']) && $result['action'] == 'resetToken') {
                $toSave = false;
                if (!empty($pageToken)) {
                    $this->syncParams['calendar']->set('pageToken', '');
                    $toSave = true;
                }

                if (empty($pageToken) && !empty($syncToken)) {
                    $this->syncParams['calendar']->set('syncToken', '');
                    $toSave = true;
                }
                if ($toSave) {
                    $this->getEntityManager()->saveEntity($this->syncParams['calendar']);
                }
            }

            return false;
        }

        foreach ($result['items'] as $item) {
            $updateResult = $this->eventManager->updateCoreEvent($item, $withCompare);
            $this->eventCounter += ($updateResult) ? self::SUCCESS_INCREMENT : self::FAIL_INCREMENT;
        }

        if (isset($result['nextPageToken'])) {
            $this->syncParams['calendar']->set('pageToken', $result['nextPageToken']);
            $this->getEntityManager()->saveEntity($this->syncParams['calendar']);

            if ($this->eventCounter < self::MAX_EVENT_COUNT) {
                $this->loadGoogleEvents($withCompare);
            }
        } else if (isset($result['nextSyncToken'])) {
            $this->syncParams['calendar']->set('pageToken', '');
            $this->syncParams['calendar']->set('syncToken', $result['nextSyncToken']);
            $this->getEntityManager()->saveEntity($this->syncParams['calendar']);
        }
        return true;
    }

    private function loadRecurrentGoogleEvents($withCompare = false)
    {
        $recurrentEvent = $this->eventManager->getRecurrentEventFromQueue();

        if (!empty($recurrentEvent)) {
            $pageToken = $recurrentEvent['pageToken'];
            $params = array();

            if (!empty($pageToken)) {
                $params['pageToken'] = $pageToken;
            }

            try {
                $result = $this->eventManager->getEventInstances($recurrentEvent['eventId'], $params);
                if (isset($result['success']) && $result['success'] === false) {
                    if (isset($result['action'])) {
                        if ($result['action'] == 'resetToken') {
                            if (!empty($pageToken)) {
                                $this->eventManager->updateRecurrentEvent($recurrentEvent['id']);
                            } else {
                                $this->eventManager->removeRecurrentEventFromQueue($recurrentEvent['id']);
                            }
                            throw new \Error("GoogleCalendarERROR: Reset pageToken for recurrent event {$recurrentEvent['id']}");
                        } else if ($result['action'] == 'deleteEvent') {
                            $this->eventManager->removeRecurrentEventFromQueue($recurrentEvent['id']);
                            throw new \Error("GoogleCalendarERROR: Delete recurrent event {$recurrentEvent['id']} frrom queue");
                        }

                    } else {
                        throw new \Error("GoogleCalendarERROR: Sync for Recurrent event {$recurrentEvent['id']} is failed");
                    }
                }

                $lastId = '';

                if (!isset($result['items']) || !is_array($result['items'])) {
                    throw new \Error("GoogleCalendarERROR: Recurrent event {$recurrentEvent['id']} insstances are not loaded");
                }

                foreach ($result['items'] as $item) {
                    $updateResult = $this->eventManager->updateCoreEvent($item, $withCompare);
                    $this->recurrentEventCounter += ($updateResult) ? self::SUCCESS_INCREMENT : self::FAIL_INCREMENT;
                    $lastId = $item['id'];
                }
                if (isset($result['nextPageToken'])) {

                    $lastIdArr = explode('_', $lastId);
                    $lastDateStr = $recurrentEvent['lastEventTime'];

                    if (is_array($lastIdArr) && !empty($lastIdArr[count($lastIdArr) - 1])) {
                        try {
                            $lastDate = new \DateTime($lastIdArr[count($lastIdArr) - 1]);
                            $lastDateStr = $lastDate->format('Y-m-d H:i:s');
                        } catch (\Exception $e) {
                            $GLOBALS['log']->error('Last recurrent id is ' . $lastId . ". " . $e->getMessage());
                        }
                    }
                    $this->eventManager->updateRecurrentEvent($recurrentEvent['id'], $result['nextPageToken'], $lastDateStr);
                } else if (isset($result['nextSyncToken'])) {
                    $this->eventManager->removeRecurrentEventFromQueue($recurrentEvent['id']);
                }
            } catch (\Exception $e) {
                $GLOBALS['log']->error('GoogleCalendarERROR: ' . $e->getMessage());
            }

            if ($this->recurrentEventCounter < self::MAX_RECURRENT_EVENT_COUNT) {
                $this->loadRecurrentGoogleEvents($withCompare);
            }
        }
        return true;
    }

    private function twoWaySync()
    {
        $this->loadGoogleEvents(true);
        $this->loadRecurrentGoogleEvents(true);
        $this->updateCoreEventsInGoogle(true);
        return true;
    }

    private function syncCoreToGC()
    {
        $this->updateCoreEventsInGoogle();
        $this->insertNewCoreEventsIntoGoogle();
    }

    private function syncGCToCore()
    {
        $this->loadGoogleEvents();
        $this->loadRecurrentGoogleEvents();
    }

    private function syncBoth()
    {
        if ($this->syncParams['isMain'] || !$this->syncParams['isInMain']) {
            $this->twoWaySync();
        } else if (!$this->syncParams['isMain'] && $this->syncParams['isInMain']) {
            $mainCalendar = $this->getEntityManager()->getRepository('GoogleCalendar')->getUsersMainCalendar($this->syncParams['userId']);
            if (!empty($mainCalendar)) {
                $this->syncParams['calendar']->set('syncToken', $mainCalendar->get('syncToken'));
                $this->syncParams['calendar']->set('pageToken', $mainCalendar->get('pageToken'));
                $this->getEntityManager()->saveEntity($this->syncParams['calendar']);
            }
        }

        if ($this->syncParams['isMain']) {
            $this->insertNewCoreEventsIntoGoogle();
        }
        return true;
    }

    public function run($calendar, $externalAccount)
    {
        if (!$this->getAcl()->checkScope('GoogleCalendar')) {
            $GLOBALS['log']->error("GoogleCalendarERROR: Access Forbiden for user {$calendar->get('userId')}");
            return false;
        }
        try {
            $this->prepareData($calendar, $externalAccount);
            $method = 'sync' . $this->syncParams['direction'];
            if (method_exists($this, $method)) {
                $this->syncParams['calendar']->set('lastLooked', $this->syncParams['startSyncTime']);
                $this->getEntityManager()->saveEntity($this->syncParams['calendar']);
                $this->$method();
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $GLOBALS['log']->error("GoogleCalendarERROR: Error when calendar synchronization is running. GoogleCalendarUser Id {$calendar->id}. Message: {$e->getMessage()}");
        }
        return true;
    }
}
