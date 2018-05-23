<?php


namespace Core\Core\Loaders;

class EmailFilterManager extends Base
{
    public function load()
    {
        $emailFilterManager = new \Core\Core\Utils\EmailFilterManager(
            $this->getContainer()->get('entityManager')
        );

        return $emailFilterManager;
    }
}

