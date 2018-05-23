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

class Report extends \Core\Core\Controllers\Record
{
    public function actionRunList($params, $data, $request)
    {
        $id = $request->get('id');
        $where = $request->get('where');

        if (empty($id)) {
            throw new BadRequest();
        }

        $maxSize = $request->get('maxSize');
        if ($maxSize > 200) {
            throw new BadRequest();
        }

        $result = $this->getRecordService()->run($id, $where, array(
            'sortBy' => $request->get('sortBy'),
            'asc' => $request->get('asc') === 'true',
            'offset' => $request->get('offset'),
            'maxSize' => $maxSize,
            'groupValue' => $request->get('groupValue')
        ));

        if ($result) {
            return array(
                'list' => $result['collection']->toArray(),
                'total' => $result['total']
            );
        }
    }

    public function actionRun($params, $data, $request)
    {
        $id = $request->get('id');
        $where = $request->get('where');

        if (empty($id)) {
            throw new BadRequest();
        }

        return $this->getRecordService()->run($id, $where);
    }

    public function actionPopulateTargetList($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        if (empty($data['id']) || empty($data['targetListId'])) {
            throw new BadRequest();
        }

        $id = $data['id'];
        $targetListId = $data['targetListId'];

        return $this->getRecordService()->populateTargetList($id, $targetListId);
    }

    public function actionSyncTargetListWithReports($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }
        if (empty($data['targetListId'])) {
            throw new BadRequest();
        }
        $targetListId = $data['targetListId'];

        $targetList = $this->getEntityManager()->getEntity('TargetList', $targetListId);
        if (!$targetList->get('syncWithReportsEnabled')) {
            throw new Error();
        }

        return $this->getRecordService()->syncTargetListWithReports($targetList);
    }

    public function getActionExportList($params, $data, $request)
    {
        $id = $request->get('id');
        $where = $request->get('where');

        if (empty($id)) {
            throw new BadRequest();
        }

        return array(
            'id' => $this->getRecordService()->exportList($request->get('id'), $where, array(
                'sortBy' => $request->get('sortBy'),
                'asc' => $request->get('asc') === 'true',
                'groupValue' => $request->get('groupValue')
            ))
        );
    }

    public function postActionExportList($params, $data, $request)
    {
        if (empty($data['id'])) {
            throw new BadRequest();
        }
        $id = $data['id'];

        $where = null;
        if (array_key_exists('where', $data)) {
            $where = json_decode(json_encode($data['where']), true);
        }

        $groupValue = null;
        if (array_key_exists('groupValue', $data)) {
            $groupValue = $data['groupValue'];
        }

        $sortBy = null;
        if (array_key_exists('sortBy', $data)) {
            $sortBy = $data['sortBy'];
        }

        $asc = true;
        if (array_key_exists('asc', $data)) {
            $asc = $data['asc'];
        }

        $params = array(
            'sortBy' => $sortBy,
            'asc' => $asc,
            'groupValue' => $groupValue
        );

        if (array_key_exists('attributeList', $data)) {
            $params['attributeList'] = $data['attributeList'];
        }
        if (array_key_exists('fieldList', $data)) {
            $params['fieldList'] = $data['fieldList'];
        }
        if (array_key_exists('format', $data)) {
            $params['format'] = $data['format'];
        }

        if (array_key_exists('ids', $data)) {
            $params['ids'] = $data['ids'];
        }

        return array(
            'id' => $this->getRecordService()->exportList($id, $where, $params)
        );
    }

    public function postActionGetEmailAttributes($params, $data, $request)
    {
        if (empty($data['id'])) {
            throw new BadRequest();
        }
        $id = $data['id'];

        $where = null;
        if (!empty($data['where'])) {
            $where = $data['where'];
            $where = json_decode(json_encode($where), true);
        }

        return $this->getServiceFactory()->create('ReportSending')->getEmailAttributes($id, $where);
    }
}

