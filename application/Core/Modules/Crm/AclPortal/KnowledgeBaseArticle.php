<?php


namespace Core\Modules\Crm\AclPortal;

use \Core\Entities\User as EntityUser;
use \Core\ORM\Entity;

class KnowledgeBaseArticle extends \Core\Core\AclPortal\Base
{

    public function checkEntityRead(EntityUser $user, Entity $entity, $data)
    {
        if (!$this->checkEntity($user, $entity, $data, 'read')) {
            return false;
        }

        if ($entity->get('status') !== 'Published') return false;

        $portalIdList = $entity->getLinkMultipleIdList('portals');

        if ($user->get('portalId') && !in_array($user->get('portalId'), $portalIdList)) {
            return false;
        }

        return true;
    }
}

