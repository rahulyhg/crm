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

namespace Core\Modules\Advanced\Controllers;

use \Core\Core\Exceptions\BadRequest;
use \Core\Core\Exceptions\Error;

class Quote extends \Core\Core\Controllers\Record
{

    public function actionGetAttributesFromOpportunity($params, $data, $request)
    {
        $opportunityId = $request->get('opportunityId');
        if (empty($opportunityId)) {
            throw new BadRequest();
        }

        return $this->getRecordService()->getAttributesFromOpportunity($opportunityId);
    }

    public function postActionGetAttributesForEmail($params, $data)
    {
        if (empty($data['quoteId']) || empty($data['templateId'])) {
            throw new BadRequest();
        }

        return $this->getRecordService()->getAttributesForEmail($data['quoteId'], $data['templateId']);
    }
}
