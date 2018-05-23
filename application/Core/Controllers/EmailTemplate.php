<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\Error;

class EmailTemplate extends \Core\Core\Controllers\Record
{
    public function actionParse($params, $data, $request)
    {
        $id = $request->get('id');
        $emailAddress = $request->get('emailAddress');
        if (empty($id)) {
            throw new Error();
        }

        return $this->getRecordService()->parse($id, array(
            'emailAddress' => $request->get('emailAddress'),
            'parentType' => $request->get('parentType'),
            'parentId' => $request->get('parentId'),
            'relatedType' => $request->get('relatedType'),
            'relatedId' => $request->get('relatedId')
        ), true);
    }

}

