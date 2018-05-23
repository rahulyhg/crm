<?php


namespace Core\Core\Formula\Functions\EntityGroup;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class IsNewType extends \Core\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('entityManager');
    }

    public function process(\StdClass $item)
    {
        return $this->getEntity()->isNew();
    }
}