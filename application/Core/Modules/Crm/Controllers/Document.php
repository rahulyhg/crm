<?php


namespace Core\Modules\Crm\Controllers;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;

class Document extends \Core\Core\Controllers\Record
{

    public function postActionGetAttachmentList($params, $data)
    {
        if (empty($data['id'])) {
            throw new BadRequest();
        }

        $id = $data['id'];

        if (!$this->getAcl()->checkScope('Attachment', 'create')) {
            throw new Forbidden();
        }

        return $this->getRecordService()->getAttachmentList($id)->toArray();
    }

}
