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

namespace Core\Modules\Advanced\Repositories;

use Core\ORM\Entity;

class GoogleCalendar extends \Core\Core\ORM\Repositories\RDB
{
    private $allowedEventTypes = ["Meeting", "Call"];

    private function validateEventTypes($types)
    {
        $selectedEventTypes = array();
        $eventTypes = (is_array($types)) ? $types : array($types);

        foreach($eventTypes as $eventType) {
            if (in_array($eventType, $this->allowedEventTypes) && !in_array($eventType, $selectedEventTypes)) {
                $selectedEventTypes[] = $eventType;
            }
        }
        return $selectedEventTypes;
    }

    public function storedUsersCalendars($userId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = "
            SELECT gc_user.*, gc.calendar_id, gc.name
            FROM google_calendar_user gc_user
                JOIN google_calendar gc ON gc_user.google_calendar_id = gc.id
            WHERE user_id = '{$userId}'
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $result = array('monitored' => array(), 'main' => array());

        foreach($res as $row) {
            $result[$row['type']][$row['google_calendar_id']] = $row;
        }

        return $result;
    }

    public function getEntitiesByGCId($userId, $eventId, $eventTypes)
    {

        $pdo = $this->getEntityManager()->getPDO();
        $results = array();

        $eventTypes = $this->validateEventTypes($eventTypes);

        foreach ($eventTypes as $eventType) {
            $table = strtolower($eventType);
            $sql = "
                SELECT id
                FROM `{$table}`
                WHERE
                    google_calendar_event_id =".$pdo->quote($eventId) . " AND
                    assigned_user_id = ".$pdo->quote($userId) . " AND
                    deleted = 0
                ORDER BY modified_at DESC
            ";
            try {
                $sth = $pdo->prepare($sql);
                $sth->execute();
                $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
                foreach($res as $row) {
                    $event = $this->getEntityManager()->getEntity($eventType, $row['id']);
                    if (!empty($event)) {
                        $results[] = $event;
                    }
                }
            } catch (\Exception $e) {
                $GLOBALS['log']->error("GoogleCalendarERROR: Failed query " . $sql . " ; " . $e->getMessage());
                return false;
            }
        }
        return $results;
    }

    public function getCalendarByGCId($googleCalendarId)
    {
        return $this->getEntityManager()->getRepository('GoogleCalendar')
                ->where(array(
                    'calendarId' => $googleCalendarId))
                ->findOne();
    }

    public function getEvents($userId, $eventTypes, $since, $to, $lastEventId, $googleCalendarId, $limit = 20)
    {
        $pdo = $this->getPDO();
        $sql = '';

        $googleCalendar = $this->getCalendarByGCId($googleCalendarId);

        if (empty($googleCalendar)) {
            return array();
        }

        $lowerLimitDateQuery = " modified_at > ".$pdo->quote($since) ;
        if (!empty($lastEventId)) {
            $lowerLimitDateQuery = " (" . $lowerLimitDateQuery .
                " OR modified_at = " . $pdo->quote($since) . " AND STRCMP(id," . $pdo->quote($lastEventId) . ")=1 )";
        }
        $eventTypes = $this->validateEventTypes($eventTypes);

        foreach ($eventTypes as $eventType) {

            $table = strtolower($eventType);

            if (!empty($sql)) {
                $sql .= " UNION ";
            }

            $sql .= "
                SELECT
                    '{$eventType}' as scope,
                    id AS id,
                    name AS name,
                    date_start AS dateStart,
                    date_end AS dateEnd,
                    google_calendar_event_id AS googleCalendarEventId,
                    modified_at AS modifiedAt,
                    description AS description,
                    deleted AS deleted,
                    status AS status

                FROM `{$table}`

                WHERE
                    {$lowerLimitDateQuery} AND
                    assigned_user_id =".$pdo->quote($userId) . " AND
                    google_calendar_event_id <> '' AND
                    google_calendar_event_id <> 'FAIL' AND
                    google_calendar_event_id IS NOT NULL AND
                    modified_at < " . $pdo->quote($to) . " AND
                    google_calendar_id ='{$googleCalendar->id}' AND
                    modified_at <> created_at
            ";
        }

        $result = array();

        if (!empty($sql)) {
            $sql .= " ORDER BY modifiedAt ASC, id ASC LIMIT {$limit}";

            try {
                $sth = $pdo->prepare($sql);
                $sth->execute();
                $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($rows as $row) {
                    $attendees = (!$row['deleted']) ? $this->getEventAttendees($row['scope'], $row['id']) : array();
                    $result[] = array_merge($row, array('attendees' => $attendees));
                }
            } catch (\Exception $e) {
                $GLOBALS['log']->error("GoogleCalendarERROR: Failed query " . $sql . " ; " . $e->getMessage());
            }

        }
        return $result;
    }

    public function getNewEvents($userId, $eventTypes, $since, $limit = 20)
    {

        $pdo = $this->getPDO();
        $sql = '';
        $result = array();

        $eventTypes = $this->validateEventTypes($eventTypes);

        foreach ($eventTypes as $eventType) {

            if (!empty($sql)) {
                $sql .= " UNION ";
            }

            $table = strtolower($eventType);

            $sql .= "
                SELECT
                    '{$eventType}' as scope,
                    id AS id,
                    name AS name,
                    date_start AS dateStart,
                    date_end AS dateEnd,
                    google_calendar_event_id AS googleCalendarEventId,
                    modified_at AS modifiedAt,
                    description AS description,
                    deleted AS deleted,
                    status AS status

                FROM `{$table}`

                WHERE
                    date_start >= ".$pdo->quote($since) . " AND
                    assigned_user_id =".$pdo->quote($userId) . " AND
                    (google_calendar_event_id ='' OR google_calendar_event_id IS NULL) AND
                    status != 'Not Held' AND
                    deleted=0
            ";
        }

        if (!empty($sql)) {
            $sql .= " ORDER BY dateStart DESC LIMIT {$limit}";

            try {
                $sth = $pdo->prepare($sql);
                $sth->execute();
                $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($rows as $row) {
                    $attendees = (!$row['deleted']) ? $this->getEventAttendees($row['scope'], $row['id']) : array();
                    $result[] = array_merge($row, array('attendees' => $attendees));
                }

            } catch (\Exception $e) {
                $GLOBALS['log']->error("GoogleCalendarERROR: Failed query " . $sql . " ; " . $e->getMessage());
            }
        }
        return $result;
    }

    public function getEventAttendees($eventType, $eventId)
    {
        if (!in_array($eventType, $this->allowedEventTypes)) {
            return false;
        }

        $pdo = $this->getPDO();

        $eventTable = strtolower($eventType);
        $relTables = array('user', 'contact', 'lead');
        $result = array();

        foreach ($relTables as $relTable){

            $relArray = array($relTable, $eventTable);
            sort($relArray);
            $relation = implode('_', $relArray);
            $scope = ucfirst($relTable);

            $sql = "
                SELECT '{$scope}' AS scope, `{$relation}`.{$relTable}_id AS id, `{$relation}`.status AS status
                FROM `{$relation}`
                WHERE `{$relation}`.deleted=0 AND `{$relation}`.{$eventTable}_id='{$eventId}' "
            ;

            try {
                $sth = $pdo->prepare($sql);
                $sth->execute();

                $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                $rows = array();
                $GLOBALS['log']->error("GoogleCalendarERROR: Failed query " . $query . " ; " . $e->getMessage());
            }

            foreach ($rows as $row) {

                $emailData = array();
                $relatedEntity = $this->getEntityManager()->getEntity($scope, $row['id']);

                if (!empty($relatedEntity)) {
                    $emailData = $this->getEntityManager()->getRepository('EmailAddress')->getEmailAddressData($relatedEntity);
                }
                $result[] = $row + array('emailData' => $emailData);
            }
        }
        return $result;
    }

    public function getUsersMainCalendar($userId)
    {
        return $this->getEntityManager()->getRepository('GoogleCalendarUser')
                ->where(array(
                    'active' => true,
                    'userId' => $userId,
                    'type' => 'main'))
                ->findOne();
    }

    public function addRecurrentEventToQueue($calendarId, $eventId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $this->removeRecurrentEventFromQueueByEventId($eventId);

        $query = "
            INSERT google_calendar_recurrent_event
                (id, google_calendar_user_id, event_id)
                VALUES
                (
                    ".$pdo->quote(uniqid()).",
                    ".$pdo->quote($calendarId).",
                    ".$pdo->quote($eventId)."
                )
        ";
        try {
            $sth = $pdo->prepare($query);
            $sth->execute();
        } catch (\Exception $e) {
            $GLOBALS['log']->error("GoogleCalendarERROR: Failed query " . $query . " ; " . $e->getMessage());
        }
    }

    public function removeRecurrentEventFromQueue($id)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $query = "
            DELETE FROM google_calendar_recurrent_event
            WHERE id=".$pdo->quote($id);
        try {
            $sth = $pdo->prepare($query);
            $sth->execute();
        } catch (\Exception $e) {
            $GLOBALS['log']->error("GoogleCalendarERROR: Failed query " . $query . " ; " . $e->getMessage());
        }
    }

    public function removeRecurrentEventFromQueueByEventId($eventId)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $query = "
            DELETE FROM google_calendar_recurrent_event
            WHERE event_id= ".$pdo->quote($eventId);
        try {
            $sth = $pdo->prepare($query);
            $sth->execute();
        } catch (\Exception $e) {
            $GLOBALS['log']->error("GoogleCalendarERROR: Failed query " . $query . " ; " . $e->getMessage());
        }
    }

    public function getRecurrentEventFromQueue($calendarId)
    {
        $maxRange = new \DateTime();
        $maxRange->modify('+6 months');

        $pdo = $this->getEntityManager()->getPDO();
        $query = "
            SELECT
                id AS id,
                event_id as eventId,
                page_token as pageToken,
                last_loaded_event_time as lastEventTime
            FROM google_calendar_recurrent_event
            WHERE deleted=0 AND google_calendar_user_id=".$pdo->quote($calendarId)."  AND (last_loaded_event_time < ".$pdo->quote($maxRange->format('Y-m-d H:i:s'))." OR last_loaded_event_time IS NULL)
            ORDER BY last_loaded_event_time ASC";
        try {
            $sth = $pdo->prepare($query);
            $sth->execute();

            $res = $sth->fetch(\PDO::FETCH_ASSOC);
            return $res;
        } catch (\Exception $e) {
            $GLOBALS['log']->error("GoogleCalendarERROR: Failed query " . $query . " ; " . $e->getMessage());
            return false;
        }
    }

    public function updateRecurrentEvent($id, $pageToken = '', $lastEventTime = null)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $query = "
            UPDATE google_calendar_recurrent_event
            SET
                page_token=".$pdo->quote($pageToken).",
                last_loaded_event_time=". ((empty($lastEventTime)) ? 'NULL' : $pdo->quote($lastEventTime)) ."
            WHERE id=".$pdo->quote($id) ;
        try {
            $sth = $pdo->prepare($query);
            $sth->execute();
        } catch (\Exception $e) {
            $GLOBALS['log']->error("GoogleCalendarERROR: Failed query " . $query . " ; " . $e->getMessage());
        }
    }

    public function deleteRecurrentInstancesFromCore($calendarId, $eventId, $eventTypes)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $eventTypes = $this->validateEventTypes($eventTypes);
        foreach ($eventTypes as $eventType) {
            $table = strtolower($eventType);
            $query = "
                UPDATE `{$table}`
                SET
                    deleted=1, google_calendar_id=NULL, google_calendar_event_id=NULL
                WHERE google_calendar_id=".$pdo->quote($calendarId) ." AND google_calendar_event_id LIKE ". $pdo->quote($eventId . '_%');
            try {
                $sth = $pdo->prepare($query);
                $sth->execute();
            } catch (\Exception $e) {
                $GLOBALS['log']->error("GoogleCalendarERROR: Failed query " . $query . " ; " . $e->getMessage());
            }
        }
        $this->removeRecurrentEventFromQueueByEventId($eventId);
    }

    public function isCalendarActive($email)
    {
        //FIX
        $calendar = $this->where(['calendarId' => $email])->findOne();
        return !empty($calendar);
    }

}
