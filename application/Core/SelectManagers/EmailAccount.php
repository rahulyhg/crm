<?php


namespace Core\SelectManagers;

class EmailAccount extends \Core\Core\SelectManagers\Base
{
    public function access(&$result)
    {
        if (!$this->user->isAdmin()) {
        	$result['whereClause'][] = array(
        		'assignedUserId' => $this->user->id
        	);
        }
    }
}

