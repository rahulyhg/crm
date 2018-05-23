<?php
/*********************************************************************************
 * The contents of this file are subject to the CoreCRM Advanced
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

namespace Core\Modules\Advanced\Hooks\Campaign;

use \Core\ORM\Entity;

class MailChimp extends \Core\Core\Hooks\Base
{
    public static $order = 9;

    public function beforeSave(Entity $entity)
    {
        if (!$entity->isNew() && $entity->isFieldChanged('mailChimpCampaignId')) {
            $entity->set('mailChimpManualSyncRun', false);
            //$entity->set('mailChimpLastSuccessfulUpdating', null);

            $this->getEntityManager()->getRepository('MailChimpLogMarker')->resetMarkers($entity->id);
        }
    }
}

