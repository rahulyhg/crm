<?php
/*********************************************************************************
 * The contents of this file are subject to the Samex CRM Advanced
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

class Quote extends \Core\Core\ORM\Repositories\RDB
{
    protected function beforeSave(Entity $entity, array $options = array())
    {
        parent::beforeSave($entity, $options);

        if (!$entity->get('accountId')) {
            $opportunityId = $entity->get('opportunityId');
            if ($opportunityId) {
                $opportunity = $this->getEntityManager()->getEntity('Opportunity', $opportunityId);
                if ($opportunity) {
                    $accountId = $opportunity->get('accountId');
                    if ($accountId) {
                        $entity->set('accountId', $accountId);
                    }
                }
            }
        }
    }
}

