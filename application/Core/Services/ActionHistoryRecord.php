<?php


namespace Core\Services;

use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\NotFound;

class ActionHistoryRecord extends Record
{
    protected $actionHistoryDisabled = true;

    protected $listCountQueryDisabled = true;

    public function loadParentNameFields(\Core\ORM\Entity $entity)
    {
        if ($entity->get('targetId') && $entity->get('targetType')) {
            $repository = $this->getEntityManager()->getRepository($entity->get('targetType'));
            if ($repository) {
                $target = $repository->where(array(
                    'id' => $entity->get('targetId')
                ))->findOne(array(
                    'withDeleted' => true
                ));
                if ($target && $target->get('name')) {
                    $entity->set('targetName', $target->get('name'));
                }
            }
        }
    }
}

