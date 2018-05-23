<?php


namespace Core\SelectManagers;

class EmailFilter extends \Core\Core\SelectManagers\Base
{
    protected function access(&$result)
    {
        if (!$this->hetUser()->isAdmin()) {
            $this->accessOnlyOwn($result);
        }
    }
}

