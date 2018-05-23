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

namespace Core\Modules\Advanced\Controllers;

use \Core\Core\Exceptions\BadRequest;

class MailChimpListGroup extends \Core\Core\Templates\Controllers\CategoryTree
{

    public static $defaultAction = 'listTree';

    public function actionListTree($params, $data, $request)
    {

        if (!$this->getAcl()->check('MailChimp')) {
            throw new Forbidden();
        }

        $where = $request->get('where');
        $listId = '';
        foreach($where as $condition) {
            if (isset($condition['field']) && $condition['field'] == 'listId') {
                $listId = $condition['value'];
                break;
            }
            if (isset($condition['attribute']) && $condition['attribute'] == 'listId') {
                $listId = $condition['value'];
                break;
            }
        }
        return array(
            'list' => $this->getService('MailChimp')->getGroupTree($listId),
            'path' => array()
        );
    }

    public function actionCreate($params, $data, $request)
    {
        if (!$this->getAcl()->check('MailChimp', 'edit')) {
            throw new Forbidden();
        }

        $service = $this->getService('MailChimp');

        if ($listGroup = $service->createListGroup($data)) {
            return $listGroup;
        }

        throw new Error();
    }
}
