<?php


namespace Core\SelectManagers;

class AuthToken extends \Core\Core\SelectManagers\Base
{
    protected function filterActive(&$result)
    {
        $result['whereClause'][] = array(
            'isActive' => true
        );
    }

    protected function filterInactive(&$result)
    {
        $result['whereClause'][] = array(
            'isActive' => false
        );
    }
}

