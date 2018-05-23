<?php


namespace Core\Modules\Crm\SelectManagers;

class CaseObj extends \Core\Core\SelectManagers\Base
{
    protected function boolFilterOpen(&$result)
    {
        $this->filterOpen($result);
    }

    protected function filterOpen(&$result)
    {
        $result['whereClause'][] = array(
            'status!=' => array('Duplicate', 'Rejected', 'Closed')
        );
    }

    protected function filterClosed(&$result)
    {
        $result['whereClause'][] = array(
            'status' => array('Closed')
        );
    }
}

