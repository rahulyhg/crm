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

namespace Core\Modules\Advanced\Reports;

use \Core\ORM\Entity;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\NotFound;

class LeadsByLastActivity extends Base
{
    protected $rangeList = array(
        array(0, 7),
        array(7, 15),
        array(15, 30),
        array(30, 60),
        array(60, 120),
        array(120, null),
        false
    );

    protected $ignoredStatusList = array(
        'Converted',
        'Recycled',
        'Dead'
    );

    /*protected function executeSubReport($where, $params)
    {
        $groupValue = $params['groupValue'];

        if ($groupValue == '-') {
            $range = false;
        } else {
            $range = explode('-', $groupValue);
            if (empty($range[1])) {
                $range[1] = null;
            }

        }

        $wherePart = $this->getWherePart($range);


        $params['customWhere'] = ' AND ' . $wherePart;
        $collection = $this->getEntityManager()->getRepository('Lead')->find($params);
        $count = $this->getEntityManager()->getRepository('Lead')->count($params);

        return array(
            'collection' => $collection,
            'total' => $count
        );
    }*/



    protected function executeSubReport($where, $params)
    {
        $groupValue = $params['groupValue'];

        if ($groupValue == '-') {
            $range = false;
        } else {
            $range = explode('-', $groupValue);
            if (empty($range[1])) {
                $range[1] = null;
            }

        }

        $selectManager = $this->getSelectManagerFactory()->create('Lead');
        $selectParams = $selectManager->buildSelectParams($params);

        $wherePart = $this->getWherePart($range);
        $selectParams['customWhere'] = ' AND ' . $wherePart;

        unset($selectParams['orderBy']);

        $collection = $this->getEntityManager()->getRepository('Lead')->find($selectParams);
        $count = $this->getEntityManager()->getRepository('Lead')->count($selectParams);

        return array(
            'collection' => $collection,
            'total' => $count
        );
    }


    public function run($where = null, array $params = null)
    {
        $reportData = $this->getDataResults();

        if (!empty($params) && array_key_exists('groupValue', $params)) {
            return $this->executeSubReport($where, $params);
        }

        $columns = array('COUNT:id');
        $groupBy = array('RANGE', 'status');

        $sums = array();

        $grouping = array(
            array(),
            array()
        );
        foreach ($this->rangeList as $i => $range) {
            $grouping[0][] = $this->getStringRange($i);
        }

        foreach ($reportData as $range => $d1) {
            $sums[$range] = array(
                'COUNT:id' => 0
            );
            foreach ($d1 as $d2) {
                $sums[$range]['COUNT:id'] += $d2['COUNT:id'];
            }
        }

        $statusList = $this->getMetadata()->get('entityDefs.Lead.fields.status.options', array());
        foreach ($statusList as $status) {
            if (!in_array($status, $this->ignoredStatusList)) {
                $grouping[1][] = $status;
            }
        }

        $columnNameMap = array(
            'COUNT:id' => $this->getLanguage()->translate('COUNT', 'functions', 'Report')
        );
        $groupNameMap = array(
            'RANGE' => array(),
            'status' => array()
        );


        foreach ($this->rangeList as $i => $r) {
            $groupNameMap['RANGE'][$this->getStringRange($i)] = $this->getRangeTranslation($i);
        }

        foreach ($grouping[1] as $status) {
            $groupNameMap['status'][$status] = $this->getLanguage()->translateOption($status, 'status', 'Lead');
        }


        $result = array(
            'type' => 'Grid',
            'groupBy' => $groupBy,
            'columns' => $columns,
            'sums' => $sums,
            'groupNameMap' => $groupNameMap,
            'columnNameMap' => $columnNameMap,
            'depth' => 2,
            'grouping' => $grouping,
            'reportData' => $reportData,
            'entityType' => 'Lead',
        );

        return $result;
    }

    protected function getStringRange($i)
    {
        $range = $this->rangeList[$i];
        return (string) $range[0] . '-' . (string) $range[1];
    }

    protected function getRangeTranslation($i)
    {
        $range = $this->rangeList[$i];
        if ($range === false) {
            return $this->getLanguage()->translate('never', 'labels', 'Report');
        } if (empty($range[1])) {
            return '>' . $range[0] . ' ' . $this->getLanguage()->translate('days', 'labels', 'Report');
        } else {
            return $range[0] . '-' . $range[1] . ' ' .$this->getLanguage()->translate('days', 'labels', 'Report');
        }
    }

    protected function getWherePart($range)
    {
        $rangePart = '';

        if (empty($range)) {
            $rangePart = " IS NULL ";
        } else {
            if (!$range[0]) {
                $rangePart = "BETWEEN DATE_SUB(NOW(), INTERVAL ".$range[1]." DAY) AND NOW()";
            } else if (!$range[1]) {
                $rangePart = " < DATE_SUB(NOW(), INTERVAL ".$range[0]." DAY)";
            } else {
                $rangePart = "BETWEEN DATE_SUB(NOW(), INTERVAL ".$range[1]." DAY) AND DATE_SUB(NOW(), INTERVAL ".$range[0]." DAY)";
            }
        }

        $sql = "
                (
                    (
                        SELECT MAX(`call`.date_start) AS 'maxDate'
                        FROM `call`
                        INNER JOIN call_lead ON `call`.id = call_lead.call_id AND call_lead.deleted=0
                        WHERE call_lead.lead_id = lead.id AND `call`.status = 'Held' AND `call`.deleted=0
                        UNION
                        SELECT MAX(meeting.date_start) AS 'maxDate'
                        FROM `meeting`
                        INNER JOIN lead_meeting ON meeting.id = lead_meeting.meeting_id AND lead_meeting.deleted=0
                        WHERE lead_meeting.lead_id = lead.id AND meeting.status = 'Held' AND meeting.deleted=0
                        ORDER BY `maxDate` DESC
                        LIMIT 1
                    ) {$rangePart}
                ) AND
                lead.status NOT IN ('".implode("', '", $this->ignoredStatusList)."')
        ";

        return $sql;
    }

    protected function getDataResults()
    {
        $pdo = $this->getEntityManager()->getPDO();

        $rangeList = $this->rangeList;

        $resultData = array();

        foreach ($rangeList as $i => $range) {

            $wherePart = $this->getWherePart($range);

            $sql = "
                SELECT COUNT(lead.id) AS 'COUNT:id', `lead`.status AS 'status'
                FROM `lead`
                WHERE
                    `lead`.deleted = 0 AND
                    {$wherePart}
                GROUP BY lead.status
            ";


            $sth = $pdo->prepare($sql);
            $sth->execute();
            $data = $sth->fetchAll();

            $dateString = $this->getStringRange($i);

            foreach ($data as $row) {
                if (!array_key_exists($dateString, $resultData)) {
                    $resultData[$dateString] = array();
                }
                $status = $row['status'];
                $resultData[$dateString][$status] = array(
                    'COUNT:id' => intval($row['COUNT:id'])
                );
            }
        }

        return $resultData;
    }
}
