<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\BadRequest;

class EmailFolder extends \Core\Core\Controllers\Record
{
    public function postActionMoveUp($params, $data, $request)
    {
        if (empty($data['id'])) {
            throw new BadRequest();
        }

        $this->getRecordService()->moveUp($data['id']);

        return true;
    }

    public function postActionMoveDown($params, $data, $request)
    {
        if (empty($data['id'])) {
            throw new BadRequest();
        }

        $this->getRecordService()->moveDown($data['id']);

        return true;
    }

    public function getActionListAll()
    {
        return $this->getRecordService()->listAll();
    }
}

