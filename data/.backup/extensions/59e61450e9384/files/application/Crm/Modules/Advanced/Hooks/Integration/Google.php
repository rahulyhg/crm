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

namespace Core\Modules\Advanced\Hooks\Integration;

use Core\ORM\Entity;
use \Core\Core\Exceptions\Error;

class Google extends \Core\Core\Hooks\Base
{
    public static $order = 20;

    protected function init()
    {
        $this->dependencies[] = 'metadata';
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    public function afterSave(Entity $entity)
    {
        if ($entity->id == 'Google' && $entity->isFieldChanged('enabled') && $entity->get('enabled')) {

            $extensions = ['curl', 'libxml', 'xml'];
            $disabled = [];
            foreach ($extensions as $extName) {
                if (!extension_loaded($extName)) {
                    $disabled[] = $extName;
                }
            }
            if (count($disabled)) {
                throw new Error("Disabled extensions: " . implode(", ", $disabled));
            }
        }
    }
}

