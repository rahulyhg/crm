<?php


namespace Core\Core\Formula;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class AttributeFetcher
{
    private $relatedEntitiesCacheMap = array();

    public function __construct()
    {
    }

    public function fetch(Entity $entity, $attribute, $getFetchedAttribute = false)
    {
        if (!is_string($attribute)) {
            throw new Error();
        }

        if (strpos($attribute, '.') !== false) {
            $arr = explode('.', $attribute);

            $key = $this->buildKey($entity, $arr[0]);
            if (!array_key_exists($key, $this->relatedEntitiesCacheMap)) {
                $this->relatedEntitiesCacheMap[$key] = $entity->get($arr[0]);
            }
            $relatedEntity = $this->relatedEntitiesCacheMap[$key];
            if ($relatedEntity && ($relatedEntity instanceof Entity) && count($arr) > 0) {
                return $this->fetch($relatedEntity, $arr[1]);
            }
            return null;
        }

        $methodName = 'get';
        if ($getFetchedAttribute) {
            $methodName = 'getFetched';
        }

        if ($entity->getAttributeParam($attribute, 'isParentName') && $methodName == 'get') {
            $relationName = $entity->getAttributeParam($attribute, 'relation');
            if ($parent = $entity->get($relationName)) {
                return $parent->get('name');
            }
        }

        return $entity->$methodName($attribute);
    }

    public function resetRuntimeCache()
    {
        $this->relatedEntitiesCacheMap = array();
    }

    protected function buildKey(Entity $entity, $link)
    {
        return spl_object_hash($entity) . '-' . $link;
    }
}