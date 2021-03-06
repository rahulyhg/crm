<?php
/*********************************************************************************
 * The contents of this file are subject to the CRM Advanced
 * Agreement ("License") which can be viewed at
 * http://www.espocrm.com/advanced-pack-agreement.
 * By installing or using this file, You have unconditionally agreed to the
 * terms and conditions of the License, and You may not use this file except in
 * compliance with the License.  Under the terms of the license, You shall not,
 * sublicense, resell, rent, lease, distribute, or otherwise  transfer rights
 * or usage to the software.
 * 
 * License ID: bcac485dee9efd0f36cf6842ad5b69b4
 ***********************************************************************************/

namespace Core\Modules\Advanced\Repositories;

use Core\ORM\Entity;

class MailChimpLogMarker extends \Core\Core\ORM\Repositories\RDB
{
    public function findMarker($campaignId, $markerType)
    {
        $marker = $this->where(array(
                'mcCampaignId' => $campaignId,
                'type' => $markerType
            ))->findOne();

        if (empty($marker)) {
            $marker = $this->getEntityManager()->getEntity("MailChimpLogMarker");
            $marker->set('mcCampaignId', $campaignId);
            $marker->set('type', $markerType);
            $this->getEntityManager()->saveEntity($marker);
        }

        return $marker;
    }

    public function resetMarkers($campaignId)
    {
        $markers = $this->where(['mcCampaignId' => $campaignId])->find();
        foreach ($markers as $marker) {
            $this->getEntityManager()->removeEntity($marker);
        }
    }

}
