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

namespace Core\Modules\Advanced\Core\Google\Items;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\NotFound;

class Event
{

    const RETURN_FORMAT_DATETIME = "Y-m-d H:i:s";
    const FORMAT_RFC_3339 = "Y-m-d\TH:i:s\Z";

    private $item;

    private $fields = array(
        'id',
        'updated',
        'end',
        'start',
        'status',
        'summary',
        'description',
    );

    private $defaults = array();

    public function __construct($jsonEvent)
    {
        $this->item = $jsonEvent;
    }

    public function getId()
    {
        return (isset($this->item['id'])) ? $this->item['id'] : '' ;
    }

    public function getDefaults()
    {
        return $this->defauls ;
    }

    public function setDefaults($value = array())
    {
        if (is_array($value)) {
            $this->defaults = array_merge($this->defaults, $value);
        }
    }

    public function getStatus()
    {
        return (isset($this->item['status'])) ? $this->item['status'] : '' ;
    }

    public function setStatus($value)
    {
        $this->item['status'] = $value;
    }

    public function getSource()
    {
        return (isset($this->item['source'])) ? $this->item['source']['title'] : '' ;
    }

    public function setSource($title = '', $url = '')
    {
        if (!empty($title) && empty($this->item['source']['title'])) {
            $this->item['source']['title'] = $title;
        }
        if (!empty($url) && empty($this->item['source']['url'])) {
            $this->item['source']['url'] = $url;
        }
    }

    public function isDeleted()
    {
        return ($this->getStatus() == 'cancelled') ? true : false ;
    }

    public function hasEnd()
    {
        return (!isset($this->item['endTimeUnspecified']));
    }

    public function restore()
    {
        $this->setStatus('confirmed');
    }

    public function isPrivate()
    {
        $visibility = (isset($this->item['visibility'])) ? $this->item['visibility'] : '';
        return (in_array($visibility, array("private", "confidential"))) ? true : false ;
    }

    public function getSummary()
    {
        return (isset($this->item['summary'])) ? $this->item['summary'] : '';
    }

    public function setSummary($value)
    {
        $this->item['summary'] = $value;
    }

    public function getDescription()
    {
        return (isset($this->item['description'])) ? $this->item['description'] : '';
    }

    public function setDescription($value)
    {
        $this->item['description'] = $value;
    }

    public function getStart()
    {
        if (isset($this->item['start'])) {
            return $this->decodeGoogleDate($this->item['start']);
        }
        return false;
    }

    public function setStart($value)
    {
        $this->item['start'] = $this->encodeGoogleDate('start', $value);
    }

    public function getEnd()
    {
        if (isset($this->item['end'])) {

            return $this->decodeGoogleDate($this->item['end']);
        }
        return false;
    }

    public function setEnd($value)
    {
        $this->item['end'] = $this->encodeGoogleDate('end', $value);
    }

    public function getRecurrence()
    {
        return (isset($this->item['recurrence'])) ? $this->item['recurrence'] : '';
    }

    public function getRecurringEventId()
    {
        return (isset($this->item['recurringEventId'])) ? $this->item['recurringEventId'] : '';
    }

    public function updated()
    {
        if (isset($this->item['updated'])) {
            $updated = new \DateTime($this->item['updated']);
            return $updated->format(self::RETURN_FORMAT_DATETIME);
        }
        return false;
    }

    protected function getCreator()
    {
        return (isset($this->item['creator'])) ? $this->item['creator'] : [];
    }

    protected function getOrganizer()
    {
        return (isset($this->item['organizer'])) ? $this->item['organizer'] : [];
    }

    public function getOrganizerEmail()
    {
        $organizer = $this->getOrganizer();
        return (isset($organizer['email'])) ? $organizer['email'] : '';
    }

    public function isOrganizer()
    {
        $organizer = $this->getOrganizer();
        return !empty($organizer['self']);
    }

    public function getAttendees()
    {
        return (isset($this->item['attendees'])) ? $this->item['attendees'] : array();
    }

    public function deleteAttendee($email)
    {
        $key = $this->getAttendeeIndex($email);
        if ($key !== false) {
            unset($this->item['attendees'][$key]);
        }
    }

    public function findAttendee($email)
    {
        $key = $this->getAttendeeIndex($email);

        if ($key !== false) {
            return $this->item['attendees'][$key];
        }
        return  false;
    }

    public function addAttendee($email, $status = 'needsAction')
    {
        $key = $this->getAttendeeIndex($email);

        if ($key === false) {
            if (!isset($this->item['attendees'])) {
                $this->item['attendees'] = array();
            }
            $this->item['attendees'][] = array('email' => $email, 'responseStatus' => $status);
            return true;
        } else {
            if ($this->item['attendees'][$key]['responseStatus'] != $status) {
                $this->item['attendees'][$key]['responseStatus'] = $status;
                return true;
            }
        }
        return false;
    }

    private function getAttendeeIndex($email)
    {
        if (isset($this->item['attendees']) && is_array($this->item['attendees'])) {
            foreach ($this->item['attendees'] as $key => $attendee) {
                if (strtolower($attendee['email']) == strtolower($email)) {
                    return $key;
                }
            }
        }
        return false;
    }


    public function build($fields = array())
    {
        return $this->item;
    }

    private function decodeGoogleDate($date)
    {
        $fieldName = (isset($date['dateTime'])) ? 'dateTime' : 'date' ;

        if (!isset($date['timeZone'])) {
            if ($fieldName == 'date') {
                $calendarTZ = (isset($this->defaults['userTimeZone'])) ? $this->defaults['userTimeZone'] : 'UTC';
            } else {
                $calendarTZ = (isset($this->defaults['timeZone'])) ? $this->defaults['timeZone'] : 'UTC';
            }
        } else {
            $calendarTZ = $date['timeZone'];
        }

        if (!$calendarTZ) {
            $calendarTZ = 'UTC';
        }

        $tz = new \DateTimeZone($calendarTZ);


        $dateTime = new \DateTime($date[$fieldName], $tz);

        $utcTZ = new \DateTimeZone('UTC');
        $dateTime->setTimeZone($utcTZ);

        return $dateTime->format(self::RETURN_FORMAT_DATETIME);

    }

    private function encodeGoogleDate($field, $date)
    {
        $result = array();
        $utcTZ = new \DateTimeZone('UTC');
        $dateTime = new \DateTime($date, $utcTZ);

        if (isset($this->item[$field])) {
            $result = $this->item[$field];
            if (isset($result['dateTime'])) {
                $result['dateTime'] = $dateTime->format(self::FORMAT_RFC_3339);
            } else {
                $result['date'] = $dateTime->format('Y-m-d');
            }
        } else {
            $result['dateTime'] = $dateTime->format(self::FORMAT_RFC_3339);
        }
        return $result;
    }

}
