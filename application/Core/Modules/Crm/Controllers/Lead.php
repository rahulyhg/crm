<?php


namespace Core\Modules\Crm\Controllers;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\BadRequest;

class Lead extends \Core\Core\Controllers\Record
{
    public function postActionConvert($params, $data, $request)
    {
        if (empty($data['id'])) {
            throw new BadRequest();
        }
        $entity = $this->getRecordService()->convert($data['id'], $data['records']);

        if (!empty($entity)) {
            return $entity->toArray();
        }
        throw new Error();
    }

    public function postActionGetConvertAttributes($params, $data, $request)
    {
        if (empty($data['id'])) {
            throw new BadRequest();
        }

        return $this->getRecordService()->getConvertAttributes($data['id']);
    }
}
