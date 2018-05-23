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

namespace Core\Modules\Advanced\Hooks\Meeting;

use Core\ORM\Entity;

class Google extends \Core\Core\Hooks\Base
{
    public static $order = 9;

    public function beforeSave(Entity $entity)
    {
        if (!$entity->isNew() && $entity->isFieldChanged('assignedUserId') && $entity->get('googleCalendarEventId') !='') {
            
            $newEntity = $this->getEntityManager()->getEntity($entity->getEntityName());
            
            $copyFields = array(
                "name", 
                "assignedUserId", 
                "googleCalendarId", 
                "googleCalendarEventId",
                "dateStart",
                "dateEnd"    
            );
            foreach ($copyFields as $field) {
                $newEntity->set($field, $entity->getFetched($field));
            }
            
            $this->getEntityManager()->saveEntity($newEntity);
            $this->getEntityManager()->removeEntity($newEntity);
            
            $entity->set('googleCalendarEventId','');
            $entity->set('googleCalendarId','');
        }
        
        if (!$entity->isNew() && $entity->getFetched('googleCalendarEventId') == 'FAIL') {
            $entity->set('googleCalendarEventId','');
        }
    
    }
    
}

