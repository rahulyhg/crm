<?php
/**LICENE**/

namespace Core\Modules\Advanced\Hooks\QuoteItem;

use Core\ORM\Entity;

class Calculating extends \Core\Core\Hooks\Base
{

    public function beforeSave(Entity $entity)
    {
        if ($entity->get('unitWeight') === null) {
            $entity->set('unitWeight', null);
        } else {
            $entity->set('weight', $entity->get('unitWeight') * $entity->get('quantity'));
        }
    }
}
