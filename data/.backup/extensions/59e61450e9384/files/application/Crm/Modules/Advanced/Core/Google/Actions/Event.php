<?php
/*********************************************************************************
 * The contents of this file are subject to the CRM Advanced
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

class Event extends Base
{
    public $calendarId;

    public $syncParams = array();

    private $googleCorePairs = array(
        'summary' => 'name',
        'start' => 'dateStart',
        'end' => 'dateEnd',
        'description' => 'description',
    );

    private $statusPairs = array(
        "needsAction" => "None",
        "accepted" => "Accepted",
        "tentative" => "Tentative",
        "declined" => "Declined",
    );

    public function getCalendarId()
    {
        return $this->calendarId;
    }

    public function setCalendarId($calendarId)
    {
        $this->calendarId = $calendarId;
    }

    protected function getClient()
    {
        return parent::getClient()->getCalendarClient();
    }

    public function getEventList($params = array())
    {
        $client = $this->getClient();
        return $client->getEventList($this->getCalendarId(), $params);
    }

    public function getEventInstances($eventId, $params = array())
    {
        $client = $this->getClient();
        return $client->getEventInstances($this->getCalendarId(), $eventId, $params);
    }

    public function insertIntoGoogle($espoEvent)
    {
        $googleEvent = $this->convertEventToGoogle($espoEvent);

        if (!empty($googleEvent)) {

            $client = $this->getClient();
            $response = $client->insertEvent($this->getCalendarId(), $googleEvent);

            if (is_array($response) && !empty($response['id'])) {

                $entity = $this->getEntityManager()->getEntity($espoEvent['scope'], $espoEvent['id']);

                if (!empty($entity)) {
                    $entity->set('googleCalendarId', $this->syncParams['calendar']->get('googleCalendarId'));
                    $entity->set('googleCalendarEventId', $response['id']);
                    $this->getEntityManager()->saveEntity($entity, array('silent' => true));
                    return true;
                }

            } else {
                $this->markAsFail($espoEvent);
            }
        }
        return false;
    }


    public function updateGoogleEvent($event, $withCompare)
    {
        $googleEvent = $this->retrieveGoogleEvent($event['googleCalendarEventId']);

        if (!is_object($googleEvent) || $googleEvent->getId() == '') {
            return false;
        }

        if ($withCompare && $event['modifiedAt'] < $googleEvent->updated() || $googleEvent->isPrivate()) {
            return false;
        }

        $changedFields = array();

        if ($googleEvent->isDeleted() != $event['deleted']) {

            if ($event['deleted']) {
                if ($this->syncParams['removeGCEventIfRemovedInCore'] ||
                    $googleEvent->getSource() == "CRM") {
                    $client = $this->getClient();
                    return $client->deleteEvent($this->getCalendarId(), $googleEvent->getId());
                }
            } else {

                $googleEvent->restore();
                $changedFields[] = 'status';
            }
        }

        $name = $googleEvent->getSummary();
        $parsedName = $this->parseGoogleEventName($name);

        if ($parsedName['name'] != $event['name'] || $parsedName['scope'] != $event['scope']) {

            $changedFields[] = 'name';
            $googleEvent->setSummary($this->convertToGoogleEventName($event['scope'], $event['name']));
        }

        foreach ($this->googleCorePairs as $googleField => $espoField) {

            $googleFieldUCF = ucfirst($googleField);

            if (
                $espoField != 'name' &&
                method_exists($googleEvent, 'get' . $googleFieldUCF) &&
                method_exists($googleEvent, 'set' . $googleFieldUCF) &&
                $googleEvent->{'get' . $googleFieldUCF}() != $event[$espoField]
            ) {

                $changedFields[] = $googleField;
                $googleEvent->{'set' . $googleFieldUCF}($event[$espoField]);
            }
        }

        if (!$this->syncParams['dontSyncEventAttendees']) {
            $modifiedAtt = false;

            $googleAttendees = $googleEvent->getAttendees();
            $googleAttendeesEmails = array();
            foreach ($googleAttendees as $gcAttendee) {
                $googleAttendeesEmails[] = $gcAttendee['email'];
            }
            foreach ($event['attendees'] as $espoAttendee) {
                $emailAddress = '';

                foreach ($espoAttendee['emailData'] as $email) {
                    if (!is_object($email)) {
                        continue;
                    }
                    if (in_array($email->emailAddress, $googleAttendeesEmails)) {
                        $emailAddress = $email->emailAddress;
                        break;
                    }
                }
                if (empty($emailAddress) && isset($espoAttendee['emailData']) && isset($espoAttendee['emailData'][0]) &&is_object($espoAttendee['emailData'][0])) {
                    $emailAddress = $espoAttendee['emailData'][0]->emailAddress;
                }

                if (!empty($emailAddress)) {
                    $gAttendeeStatus = array_search($espoAttendee['status'], $this->statusPairs);
                    if ($espoAttendee['id'] == $this->syncParams['userId']) {
                        if (in_array($emailAddress, $googleAttendeesEmails) || count($event['attendees']) > 1) {
                            $modifiedAtt |= $googleEvent->addAttendee($emailAddress, $gAttendeeStatus);
                        }
                    } else {
                        $modifiedAtt |= $googleEvent->addAttendee($emailAddress, $gAttendeeStatus);
                    }
                }
            }

            if ($modifiedAtt) {
                $changedFields[] = 'attendees';
            }
        }

        if (!empty($changedFields)) {
            $client = $this->getClient();
            $res = $client->updateEvent($this->getCalendarId(), $googleEvent->getId(), $googleEvent->build());
            if (!$res) {
                $this->markAsFail($event);
            } else {
                return true;
            }
        }
        return false;
    }

    public function updateCoreEvent($event, $withCompare = true)
    {
        $googleEvent = $this->asGoogleEvent($event);
        $parsedName = $this->parseGoogleEventName($googleEvent->getSummary());

        $scope = $parsedName['scope'];
        $name = $parsedName['name'];

        if (!$this->getAcl()->check($scope, 'edit')) {
            return false;
        }

        if ($googleEvent->isDeleted()) {
            $this->deleteRecurrentInstancesFromCore($googleEvent->getId());
        }
        if ($googleEvent->getRecurrence() != '' && $googleEvent->getRecurringEventId() == '' ) {
                $this->deleteRecurrentInstancesFromCore($googleEvent->getId());
                if (!$googleEvent->isPrivate() && $googleEvent->hasEnd()) {
                    $this->addRecurrentEventToQueue($googleEvent->getId());
                }
            return false;
        }
        if (!$googleEvent->isDeleted() && $googleEvent->getStart() < $this->syncParams['fetchSince']) {
            return false;
        }
       /* if (!$googleEvent->isOrganizer()) {
            if ($this->getEntityManager()->getRepository('GoogleCalendar')->isCalendarActive($googleEvent->getOrganizerEmail())) {
                return false;
            }
        }
        */
        $espoEvents = $this->getEntityManager()->getRepository('GoogleCalendar')->getEntitiesByGCId(
            $this->syncParams['userId'],
            $googleEvent->getId(),
            $this->syncParams['syncEntities']
        );

        if (empty($espoEvents)) {
            if (in_array($scope, $this->syncParams['syncEntities'])) {
                $espoEvents = array($this->getEntityManager()->getEntity($scope));
            } else {
                return false;
            }
        }

        foreach ($espoEvents as $espoEvent) {
            if (!is_object($espoEvent) || !in_array($espoEvent->getEntityName(), $this->syncParams['syncEntities'])) {
                continue;
            }

            if ($googleEvent->isDeleted() || $googleEvent->isPrivate() || !$googleEvent->hasEnd()) {
                if ($espoEvent) {
                    if (!$espoEvent->isNew() && $this->getAcl()->check($espoEvent->getEntityName(), 'delete')) {
                        $this->getEntityManager()->removeEntity($espoEvent, array('silent' => true));
                    }
                }
                continue;
            }

            if ($scope != $espoEvent->getEntityName() && in_array($scope, $this->syncParams['syncEntities'])) {
                if ($googleEvent->getSource() != "CRM") {

                    $oldValues = array();
                    $espoEvent->loadLinkMultipleField('users', array('status' => "acceptanceStatus"));
                    $espoEvent->loadLinkMultipleField('contacts', array('status' => "acceptanceStatus"));
                    $espoEvent->loadLinkMultipleField('leads', array('status' => "acceptanceStatus"));

                    foreach ($espoEvent->fields as $field => $fieldParams) {

                        if ($field == 'id') {
                            continue;
                        }
                        $oldValues[$field] = $espoEvent->get($field);
                    }
                    if ($espoEvent) {
                        $this->getEntityManager()->removeEntity($espoEvent, array('silent' => true));
                    }

                    $espoEvent = $this->getEntityManager()->getEntity($scope);
                    $espoEvent->populateFromArray($oldValues);
                }
            }

            if (!in_array($espoEvent->getEntityName(), $this->syncParams['syncEntities'])) {
                continue;
            }

            if (!$espoEvent->isNew() && $withCompare && $espoEvent->get('modifiedAt') > $googleEvent->updated()) {
                continue;
            }
            $isModified = false;

            if (!$espoEvent->isNew() && $espoEvent->get('googleCalendarId') != $this->syncParams['calendar']->get('googleCalendarId')) {
                $espoEvent->set('googleCalendarId', $this->syncParams['calendar']->get('googleCalendarId'));
                $isModified = true;
            }
            foreach ($this->googleCorePairs as $googleField => $espoField) {
                if ($espoField == 'name') {
                    $googleValue = $name;
                } else {
                    if (!method_exists($googleEvent, 'get' . ucfirst($googleField))) {
                        continue;
                    }
                    $googleValue = $googleEvent->{'get' . ucfirst($googleField)}();
                }

                if ($espoEvent->isNew()) {
                    $espoEvent->set($espoField, $googleValue);
                } else {
                    if ($espoEvent->get($espoField) != $googleValue) {
                        $espoEvent->set($espoField, $googleValue);
                        $isModified = true;
                    }
                }
            }
            $attendeeFields = array(
                'usersIds',
                'contactsIds',
                'leadsIds',
                'usersColumns',
                'contactsColumns',
                'leadsColumns'
            );

            if ($espoEvent->isNew()) {
                $userId = $this->syncParams['userId'];
                $espoEvent->set('assignedUserId', $userId);
                $dateCore = new \DateTime($espoEvent->get('dateEnd'));
                $dateNow = new \DateTime();
                if ($dateCore < $dateNow) {
                    $espoEvent->set('status', 'Held');
                }

                foreach ($googleEvent->getAttendees() as $gAttendee) {
                    if (!empty($gAttendee['email'])) {
                        $entity = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddress($gAttendee['email']);
                        if (!empty($entity)) {
                            $id = $entity->id;
                            $entityName = strtolower($entity->getEntityName());

                            ${$entityName ."sIds"}[] = $id;

                            if (!isset(${$entityName . "sColumns"})) {
                                ${$entityName . "sColumns"} = new \stdClass();
                            }
                            $columnData = new \stdClass();
                            $columnData->status = $this->statusPairs[$gAttendee['responseStatus']];
                            ${$entityName . "sColumns"}->$id = $columnData;
                        }
                    }
                }
                if (empty($usersIds) || !in_array($userId, $usersIds)) {
                    $usersIds[] = $userId;
                    if (!isset($usersColumns)) {
                        $usersColumns = new \stdClass();
                    }
                    $columnData = new \stdClass();
                    $columnData->status = 'None';
                    $usersColumns->$userId = $columnData;
                }
                foreach ($attendeeFields as $attendeeField) {
                    if (isset($$attendeeField) && count($$attendeeField) > 0) {
                        $espoEvent->set($attendeeField, $$attendeeField);
                    }
                }
                $espoEvent->set('googleCalendarId', $this->syncParams['calendar']->get('googleCalendarId'));
                $espoEvent->set('googleCalendarEventId', $googleEvent->getId());

            } else {

                $espoAttendees = $this->getEntityManager()->getRepository('GoogleCalendar')->getEventAttendees($espoEvent->getEntityName(), $espoEvent->id);
                $espoEvent->loadLinkMultipleField('users', array('status' => "acceptanceStatus"));
                $espoEvent->loadLinkMultipleField('contacts', array('status' => "acceptanceStatus"));
                $espoEvent->loadLinkMultipleField('leads', array('status' => "acceptanceStatus"));
                foreach ($googleEvent->getAttendees() as $gAttendee) {
                    if (!empty($gAttendee['email'])) {

                        $emailOwner = '';
                        foreach ($espoAttendees as $eAttendee) {
                            if (!empty($eAttendee['emailData']) && is_array($eAttendee['emailData'])) {
                                foreach ($eAttendee['emailData'] as $email) {
                                    if (is_object($email) && strtolower($email->emailAddress) == strtolower($gAttendee['email'])) {
                                        $emailOwner = $eAttendee;
                                        break 2;
                                    }
                                }
                            }
                        }

                        if (empty($emailOwner)) {
                            $entity = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddress($gAttendee['email']);
                            $entityName = (!empty($entity)) ? strtolower($entity->getEntityName()) : '';
                            if (in_array($entityName ."sIds", $attendeeFields)) {
                                $id = $entity->id;

                                ${$entityName ."sIds"} = $espoEvent->get($entityName ."sIds");
                                ${$entityName ."sIds"}[] = $entity->id;

                                ${$entityName . "sColumns"}  = $espoEvent->get($entityName . "sColumns");

                                if (!isset(${$entityName . "sColumns"})) {
                                    ${$entityName . "sColumns"} = new \stdClass();
                                }
                                $columnData = new \stdClass();
                                $columnData->status = $this->statusPairs[$gAttendee['responseStatus']];
                                ${$entityName . "sColumns"}->$id = $columnData;

                                $espoEvent->set($entityName . "sIds", ${$entityName ."sIds"});
                                $espoEvent->set($entityName . "sColumns", ${$entityName . "sColumns"});

                                $isModified = true;
                            }
                        } else {
                            if ($eAttendee['status'] != $this->statusPairs[$gAttendee['responseStatus']] &&
                                $this->statusPairs[$gAttendee['responseStatus']] != "None")
                            {
                                $entityName = strtolower($eAttendee['scope']);
                                $entityId = $eAttendee['id'];
                                ${$entityName . "sColumns"}  = $espoEvent->get($entityName . "sColumns");
                                ${$entityName . "sColumns"}->$entityId->status = $this->statusPairs[$gAttendee['responseStatus']];

                                $espoEvent->set($entityName . "sColumns", ${$entityName . "sColumns"});
                                $isModified = true;
                            }
                        }
                    }
                }
            }
            if ($espoEvent->isNew() || $isModified) {
                $this->getEntityManager()->saveEntity($espoEvent, array('silent' => true));
            }
        }
        return true;
    }

    public function convertEventToGoogle($espoEvent)
    {

        $googleEvent = $this->asGoogleEvent();
        $espoEvent['name'] = $this->convertToGoogleEventName($espoEvent['scope'], $espoEvent['name']);

        foreach ($this->googleCorePairs as $googleField => $espoField) {

            if (method_exists($googleEvent, 'set' . ucfirst($googleField)) && isset($espoEvent[$espoField])) {
                $googleEvent->{'set' . ucfirst($googleField)}($espoEvent[$espoField]);
            }
        }

        if (!$this->syncParams['dontSyncEventAttendees']) {
            foreach($espoEvent['attendees'] as $attendee) {
                if (!empty($attendee['emailData'])) {
                    if ($attendee['id'] != $this->syncParams['userId'] || count($espoEvent['attendees']) > 1) {
                        $googleEvent->addAttendee(
                            $attendee['emailData'][0]->emailAddress,
                            array_search($attendee['status'], $this->statusPairs)
                        );
                    }
                }
            }
        }

        $siteUrl = rtrim($this->config->get('siteUrl'), '/');
        $url = $siteUrl . '/#' . $espoEvent['scope'] . '/view/' . $espoEvent['id'];

        $googleEvent->setSource('CRM', $url);

        return $googleEvent->build();
    }

    public function parseGoogleEventName($value)
    {
        $scope = $this->syncParams['defaultEntity'];
        $name = $value;

        foreach ($this->syncParams['entityLabels'] as $entityType => $label) {
            if (!empty($label)) {
                $pattern = "/^{$label}[':',' ','-']+/i";

                $matchRes = preg_match_all($pattern, $value, $matches);
                if ($matchRes > 0) {
                    $scope = $entityType;
                    $name = preg_replace($pattern, '', $value, 1);
                    if (empty($name)) {
                        $name = $value;
                    }
                    break;
                }
            } else {
                $scope = $entityType;
                break;
            }
        }
        return array('scope' => $scope, 'name' => $name);
    }

    public function convertToGoogleEventName($scope, $name)
    {
        $label = $this->syncParams['entityLabels'][$scope];
        if (!empty($label)) {
            $label .= ": ";
        }
        return $label . $name;
    }

    public function retrieveGoogleEvent($id)
    {
        $client = $this->getClient();
        $event = $client->retrieveEvent($this->getCalendarId(), $id);
        if (!empty($event)) {
            return $this->asGoogleEvent($event);
        }
        return false;
    }

    private function asGoogleEvent($event = [])
    {
         $googleEvent = new \Core\Modules\Advanced\Core\Google\Items\Event($event);
         $googleEvent->setDefaults( array(
            'timeZone' => $this->syncParams['googleTimeZone'],
            'userTimeZone' => $this->syncParams['userTimeZone']));

         return $googleEvent;
    }

    private function addRecurrentEventToQueue($eventId)
    {
       return $this->getEntityManager()->getRepository('GoogleCalendar')->addRecurrentEventToQueue(
            $this->syncParams['calendar']->id,
            $eventId
        );
    }

    public function getRecurrentEventFromQueue()
    {
        return $this->getEntityManager()->getRepository('GoogleCalendar')->getRecurrentEventFromQueue(
            $this->syncParams['calendar']->id
        );
    }

    public function updateRecurrentEvent($id, $pageToken = '', $lastEventTime = null)
    {
        $this->getEntityManager()->getRepository('GoogleCalendar')->updateRecurrentEvent($id, $pageToken, $lastEventTime);
    }

    public function removeRecurrentEventFromQueue($id)
    {
        $this->getEntityManager()->getRepository('GoogleCalendar')->removeRecurrentEventFromQueue($id);
    }

    public function deleteRecurrentInstancesFromCore($id)
    {
        $this->getEntityManager()->getRepository('GoogleCalendar')->deleteRecurrentInstancesFromCore($this->syncParams['calendar']->get('googleCalendarId'), $id, $this->syncParams['syncEntities']);
    }

    public function markAsFail($espoEvent)
    {
        $entity = $this->getEntityManager()->getEntity($espoEvent['scope'], $espoEvent['id']);
        if (!empty($entity)) {
            $entity->set('googleCalendarEventId', 'FAIL');
            $entity->set('googleCalendarId', '');
            $this->getEntityManager()->saveEntity($entity, array('silent' => true));
        }
    }
}
