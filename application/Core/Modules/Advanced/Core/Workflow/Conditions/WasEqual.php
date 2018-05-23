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

namespace Core\Modules\Advanced\Core\Workflow\Conditions;

use Core\Modules\Advanced\Core\Workflow\Utils;

class WasEqual extends Base
{
    protected function compare($fieldValue)
    {
        $entity = $this->getEntity();
        $fieldName = $this->getFieldName();

        $previousFieldValue = $entity->getFetched($fieldName);
        if (isset($previousFieldValue)) {
            $previousFieldValue = Utils::strtolower($previousFieldValue);
        }

        $subjectValue = $this->getSubjectValue();

        return ($subjectValue == $previousFieldValue);
    }

}