<?php


namespace Core\Core\Controllers;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\BadRequest;
use \Core\Core\Utils\Util;

class RecordTree extends Record
{
    public static $defaultAction = 'list';

    protected $defaultRecordServiceName = 'RecordTree';

    public function actionListTree($params, $data, $request)
    {
        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Forbidden();
        }

        $where = $request->get('where');
        $parentId = $request->get('parentId');
        $maxDepth = $request->get('maxDepth');
        $onlyNotEmpty = $request->get('onlyNotEmpty');

        $collection = $this->getRecordService()->getTree($parentId, array(
            'where' => $where,
            'onlyNotEmpty' => $onlyNotEmpty
        ), 0, $maxDepth);
        return array(
            'list' => $collection->toArray(),
            'path' => $this->getRecordService()->getTreeItemPath($parentId)
        );
    }
}

