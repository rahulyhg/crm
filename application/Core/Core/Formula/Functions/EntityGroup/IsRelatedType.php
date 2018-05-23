<?php


namespace Core\Core\Formula\Functions\EntityGroup;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class IsRelatedType extends \Core\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('entityManager');
    }

    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            throw new Error();
        }

        if (!is_array($item->value)) {
            throw new Error();
        }

        if (count($item->value) < 2) {
            throw new Error();
        }

        $link = $this->evaluate($item->value[0]);
        $id = $this->evaluate($item->value[1]);

        return $this->getInjection('entityManager')->getRepository($this->getEntity()->getEntityType())->isRelated($this->getEntity(), $link, $id);
    }

}