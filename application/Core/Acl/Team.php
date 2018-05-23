<?php


namespace Core\Acl;

use \Core\ORM\Entity;

class Team extends \Core\Core\Acl\Base
{
    public function checkInTeam(\Core\Entities\User $user, Entity $entity)
    {
        $userTeamIdList = $user->getLinkMultipleIdList('teams');
        return in_array($entity->id, $userTeamIdList);
    }
}