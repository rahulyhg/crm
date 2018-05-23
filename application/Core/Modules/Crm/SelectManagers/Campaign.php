<?php


namespace Core\Modules\Crm\SelectManagers;

class Campaign extends \Core\Core\SelectManagers\Base
{
    protected function filterActive(&$result)
    {
        $result['whereClause'][] = array(
            'status' => 'Active'
        );
    }

 }

