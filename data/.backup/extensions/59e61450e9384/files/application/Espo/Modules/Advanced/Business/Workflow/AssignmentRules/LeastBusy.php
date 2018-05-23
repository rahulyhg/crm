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

namespace Core\Modules\Advanced\Business\Workflow\AssignmentRules;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class LeastBusy
{
    private $entityManager;

    private $selectManager;

    private $entityType;

    private $reportService;

    public function __construct($entityManager, $selectManager, $reportService, $entityType)
    {
        $this->entityManager = $entityManager;
        $this->selectManager = $selectManager;
        $this->reportService = $reportService;
        $this->entityType = $entityType;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getSelectManager()
    {
        return $this->selectManager;
    }

    protected function getEntityType()
    {
        return $this->entityType;
    }

    protected function getReportService()
    {
        return $this->reportService;
    }

    public function getAssignmentAttributes(Entity $entity, $targetTeamId, $targetUserPosition, $listReportId = null)
    {
        $team = $this->getEntityManager()->getEntity('Team', $targetTeamId);

        if (!$team) {
            $GLOBALS['log']->error('LeastBusy: No team with id ' . $targetTeamId);
            throw new Error();
        }

        $userSelectParams = array(
            'select' => ['id'],
            'orderBy' => 'userName'
        );
        if (!empty($targetUserPosition)) {
            $userSelectParams['additionalColumnsConditions'] = array(
                'role' => $targetUserPosition
            );
        }

        $userList = $this->getEntityManager()->getRepository('Team')->findRelated($team, 'users', $userSelectParams);

        if (count($userList) == 0) {
            $GLOBALS['log']->error('LeastBusy: No users found in team ' . $targetTeamId);
            throw new Error();
        }

        $userIdList = [];
        foreach ($userList as $user) {
            $userIdList[] = $user->id;
        }

        $counts = array();
        foreach ($userIdList as $id) {
            $counts[$id] = 0;
        }

        if ($listReportId) {
            $report = $this->getEntityManager()->getEntity('Report', $listReportId);
            if (!$report) {
                throw new Error();
            }
            $this->getReportService()->checkReportIsPosibleToRun($report);
            $selectParams = $this->getReportService()->fetchSelectParamsFromListReport($report);
        } else {
            $selectParams = $this->getSelectManager()->getEmptySelectParams();
        }

        $selectParams['whereClause'][] = array(
            'assignedUserId' => $userIdList,
            'id!=' => $entity->id,
        );
        $selectParams['groupBy'] = ['assignedUserId'];
        $selectParams['select'] = ['assignedUserId', 'COUNT:id'];
        $selectParams['orderBy'] = [[1, false]];

        $this->getSelectManager()->addJoin(['assignedUser', 'assignedUser'], $selectParams);
        $selectParams['whereClause'][] = ['assignedUser.isActive' => true];

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery($this->getEntityType(), $selectParams);

        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $rowList = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $userId = null;

        foreach ($rowList as $row) {
            $id = $row['assignedUserId'];
            if (!$id) continue;
            $counts[$id] = $row['COUNT:id'];
        }

        $minCount = null;

        $minCountIdList = [];

        foreach ($counts as $id => $count) {
            if (is_null($minCount) || $count <= $minCount) {
                $minCount = $count;
                $minCountIdList[] = $id;
            }
        }

        $attributes = array();

        if (count($minCountIdList)) {
            $attributes['assignedUserId'] = $minCountIdList[array_rand($minCountIdList)];

            if ($attributes['assignedUserId']) {
                $user = $this->getEntityManager()->getEntity('User', $attributes['assignedUserId']);
                if ($user) {
                    $attributes['assignedUserName'] = $user->get('name');
                }
            }
        }

        return $attributes;
    }
}
