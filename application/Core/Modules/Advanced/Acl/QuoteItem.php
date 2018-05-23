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

namespace Core\Modules\Advanced\Acl;

use \Core\Entities\User;
use \Core\ORM\Entity;

class QuoteItem extends \Core\Core\Acl\Base
{
    public function checkIsOwner(User $user, Entity $entity)
    {
        if ($entity->has('quoteId')) {
            $quoteId = $entity->get('quoteId');
            if (!$quoteId) return;

            $quote = $this->getEntityManager()->getEntity('Quote', $quoteId);
            if ($quote && $this->getAclManager()->getImplementation('Quote')->checkIsOwner($user, $quote)) {
                return true;
            }
        } else {
            return parent::checkIsOwner($user, $entity);
        }
    }

    public function checkInTeam(User $user, Entity $entity)
    {
        if ($entity->has('quoteId')) {
            $quoteId = $entity->get('quoteId');
            if (!$quoteId) return;

            $quote = $this->getEntityManager()->getEntity('Quote', $quoteId);
            if ($quote && $this->getAclManager()->getImplementation('Quote')->checkInTeam($user, $quote)) {
                return true;
            }
        } else {
            return parent::checkInTeam($user, $entity);
        }
    }
}

