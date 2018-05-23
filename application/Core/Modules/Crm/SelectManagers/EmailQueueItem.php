<?php


namespace Core\Modules\Crm\SelectManagers;

class EmailQueueItem extends \Core\Core\SelectManagers\Base
{

    protected function filterPending(&$result)
    {
        $result['whereClause'][] = array(
            'status=' => 'Pending'
        );
    }

    protected function filterSent(&$result)
    {
        $result['whereClause'][] = array(
            'status=' => 'Sent'
        );
    }

    protected function filterFailed(&$result)
    {
        $result['whereClause'][] = array(
            'status=' => 'Failed'
        );
    }
}

