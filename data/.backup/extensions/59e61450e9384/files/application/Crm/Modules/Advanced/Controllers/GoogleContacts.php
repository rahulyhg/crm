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

namespace Core\Modules\Advanced\Controllers;

class GoogleContacts extends \Core\Core\Controllers\Base
{
    public function actionUsersContactsGroups($params, $data, $request)
    {
        return $this->getService('GoogleContacts')->usersContactsGroups();
    }

    public function actionPush($params, $data, $request)
    {
        if (!$this->getAcl()->checkScope($this->name)) {
            throw new Forbidden();
        }
        $entityType = $data['entityType'];
        $params = array();
        if (isset($data['byWhere']) && $data['byWhere']) {
            $params['where'] = array();
            foreach ($data['where'] as $cause) {
                $params['where'][] = (array) $cause;
            }
        } else {
            $params['ids'] = $data['idList'];
        }
        return array('count' => $this->getService('GoogleContacts')->push($entityType, $params));
    }
}
