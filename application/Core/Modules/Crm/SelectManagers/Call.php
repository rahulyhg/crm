<?php


namespace Core\Modules\Crm\SelectManagers;

class Call extends \Core\Core\SelectManagers\Base
{
    protected function accessOnlyOwn(&$result)
    {
        $this->addJoin('users', $result);
        $result['whereClause'][] = array(
            'OR' => array(
                'usersMiddle.userId' => $this->getUser()->id,
                'assignedUserId' => $this->getUser()->id
            )
        );
    }

    protected function accessOnlyTeam(&$result)
    {
        $this->setDistinct(true, $result);
        $this->addLeftJoin(['teams', 'teamsAccess'], $result);
        $this->addLeftJoin(['users', 'usersAccess'], $result);

        $result['whereClause'][] = array(
            'OR' => array(
                'teamsAccess.id' => $this->getUser()->getLinkMultipleIdList('teams'),
                'usersAccess.id' => $this->getUser()->id,
                'assignedUserId' => $this->getUser()->id
            )
        );
    }

    protected function boolFilterOnlyMy(&$result)
    {
        $this->addJoin('users', $result);
        $result['whereClause'][] = array(
            'users.id' => $this->getUser()->id,
            'OR' => array(
                'usersMiddle.status!=' => 'Declined',
                'usersMiddle.status=' => null
            )
        );
    }

    protected function filterPlanned(&$result)
    {
        $result['whereClause'][] = array(
        	'status' => 'Planned'
        );
    }

    protected function filterHeld(&$result)
    {
        $result['whereClause'][] = array(
        	'status' => 'Held'
        );
    }

    protected function filterTodays(&$result)
    {
        $result['whereClause'][] = $this->convertDateTimeWhere(array(
        	'type' => 'today',
        	'field' => 'dateStart',
        	'timeZone' => $this->getUserTimeZone()
        ));
    }
}

