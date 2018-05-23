<?php


namespace Core\Modules\Crm\SelectManagers;

class Document extends \Core\Core\SelectManagers\Base
{
    protected function filterActive(&$result)
    {
        $result['whereClause'][] = array(
            'status' => 'Active'
        );
    }

    protected function filterDraft(&$result)
    {
        $result['whereClause'][] = array(
            'status' => 'Draft'
        );
    }

 }

