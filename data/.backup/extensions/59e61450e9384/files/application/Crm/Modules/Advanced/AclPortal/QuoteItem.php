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

namespace Core\Modules\Advanced\AclPortal;

use \Core\Entities\User;
use \Core\ORM\Entity;

class QuoteItem extends \Core\Core\AclPortal\Base
{
    public function checkInAccount(User $user, Entity $entity)
    {
        if ($entity->has('quoteId')) {
            $quoteId = $entity->get('quoteId');
            if (!$quoteId) return;

            $quote = $this->getEntityManager()->getEntity('Quote', $quoteId);
            if ($quote && $this->getAclManager()->getImplementation('Quote')->checkInAccount($user, $quote)) {
                return true;
            }
        } else {
            return parent::checkInAccount($user, $entity);
        }
    }

    public function checkIsOwnContact(User $user, Entity $entity)
    {
        if ($entity->has('quoteId')) {
            $quoteId = $entity->get('quoteId');
            if (!$quoteId) return;

            $quote = $this->getEntityManager()->getEntity('Quote', $quoteId);
            if ($quote && $this->getAclManager()->getImplementation('Quote')->checkIsOwnContact($user, $quote)) {
                return true;
            }
        } else {
            return parent::checkIsOwnContact($user, $entity);
        }
    }
}

