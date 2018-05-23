<?php


namespace Core\Core\Formula\Functions\EntityGroup;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class AddLinkMultipleIdType extends \Core\Core\Formula\Functions\Base
{
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

        if (!is_string($link)) {
            throw new Error();
        }
        if (!is_string($id)) {
            throw new Error();
        }

        $this->getEntity()->addLinkMultipleId($link, $id);
    }
}