<?php


namespace Core\SelectManagers;

class ScheduledJob extends \Core\Core\SelectManagers\Base
{
    protected function access(&$result)
    {
        parent::access($result);

        $result['whereClause'] = array(
            'isInternal' => false
        );
    }
}
