<?php


namespace Core\Core\Formula\Functions;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class AttributeType extends Base
{
    static public $hasAttributeFetcher = true;

    protected $attributeFetcher;

    public function setAttributeFetcher(\Core\Core\Formula\AttributeFetcher $attributeFetcher)
    {
        $this->attributeFetcher = $attributeFetcher;
    }

    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            throw new Error();
        }

        if (is_array($item->value)) {
            $arr = [];
            foreach ($item->value as $attribute) {
                $arr[] = $this->getAttributeValue($attribute);
            }
            return $arr;
        }

        return $this->getAttributeValue($item->value);
    }

    protected function getAttributeValue($attribute)
    {
        return $this->attributeFetcher->fetch($this->getEntity(), $attribute);
    }
}