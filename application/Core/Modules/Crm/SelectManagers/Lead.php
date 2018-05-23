<?php


namespace Core\Modules\Crm\SelectManagers;

class Lead extends \Core\Core\SelectManagers\Base
{
    protected function filterActive(&$result)
    {
        $result['whereClause'][] = array(
            'status!=' => ['Converted', 'Recycled', 'Dead']
        );
    }

    protected function filterActual(&$result)
    {
        $result['whereClause'][] = array(
            'status!=' => ['Converted', 'Recycled', 'Dead']
        );
    }

    protected function filterConverted(&$result)
    {
        $result['whereClause'][] = array(
            'status=' => 'Converted'
        );
    }
 }

