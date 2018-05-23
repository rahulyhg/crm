<?php


namespace Core\Modules\Crm\Acl;

use \Core\Entities\User;
use \Core\ORM\Entity;

class CampaignLogRecord extends \Core\Core\Acl\Base
{

    public function checkIsOwner(User $user, Entity $entity)
    {
        if ($entity->has('campaignId')) {
            $campaignId = $entity->get('campaignId');
            if (!$campaignId) return;

            $campaign = $this->getEntityManager()->getEntity('Campaign', $campaignId);
            if ($campaign && $this->getAclManager()->getImplementation('Campaign')->checkIsOwner($user, $campaign)) {
                return true;
            }
        }
        return false;
    }

    public function checkInTeam(User $user, Entity $entity)
    {
        if ($entity->has('campaignId')) {
            $campaignId = $entity->get('campaignId');
            if (!$campaignId) return;

            $campaign = $this->getEntityManager()->getEntity('Campaign', $campaignId);
            if ($campaign && $this->getAclManager()->getImplementation('Campaign')->checkInTeam($user, $campaign)) {
                return true;
            }
        }
        return false;
    }
}

