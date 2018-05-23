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

class MailChimpList extends \Core\Core\Controllers\Base
{

    public static $defaultAction = 'list';

    public function actionList($params, $data, $request)
    {

        if (!$this->getAcl()->check('MailChimp')) {
            throw new Forbidden();
        }

        $where = $request->get('where');
        $offset = $request->get('offset');
        $maxSize = $request->get('maxSize');
        $asc = $request->get('asc') === 'true';
        $sortBy = $request->get('sortBy');
        $q = $request->get('q');

        $nameFilter = '';
        if (!empty($q)) {
            $nameFilter = $q;
        } else if (!empty($where)) {
            $nameFilter = $where[0]['value'];
        }

        $result = $this->getService('MailChimp')->getListsByOffset( array(
            'offset' => $offset,
            'maxSize' => $maxSize,
            'asc' => $asc,
            'sortBy' => $sortBy,
            'filter' => $nameFilter,
            //'q' => $q,
            )
        );

        return $result;
    }

    public function actionCreate($params, $data, $request)
    {
        if (!$this->getAcl()->check('MailChimp')) {
            throw new Forbidden();
        }

        $service = $this->getService('MailChimp');

        if ($list = $service->createList($data)) {
            return $list;
        }

        throw new Error();
    }
}
