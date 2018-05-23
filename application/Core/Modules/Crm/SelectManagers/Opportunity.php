<?php


namespace Core\Modules\Crm\SelectManagers;

class Opportunity extends \Core\Core\SelectManagers\Base
{
    protected function filterOpen(&$result)
    {
        $result['whereClause'][] = array(
            'stage!=' => ['Closed Won', 'Closed Lost']
        );
    }

    protected function filterWon(&$result)
    {
        $result['whereClause'][] = array(
            'stage=' => 'Closed Won'
        );
    }

    protected function filterLost(&$result)
    {
        $result['whereClause'][] = array(
            'stage=' => 'Closed Lost'
        );
    }

 }

