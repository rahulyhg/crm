<?php


namespace Core\Modules\Crm\Repositories;

use Core\ORM\Entity;

class Opportunity extends \Core\Core\ORM\Repositories\RDB
{
    public function beforeSave(Entity $entity, array $options = array())
    {
        if ($entity->isNew()) {
            if (!$entity->has('probability') && $entity->get('stage')) {
                $probability = $this->getMetadata()->get('entityDefs.Opportunity.probabilityMap.' . $entity->get('stage'), 0);
                $entity->set('probability', $probability);
            }
        }

        parent::beforeSave($entity, $options);
    }
}
