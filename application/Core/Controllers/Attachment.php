<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;

class Attachment extends \Core\Core\Controllers\Record
{
    public function actionUpload($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        if (!$this->getAcl()->checkScope('Attachment', 'create')) {
            throw new Forbidden();
        }

        $arr = explode(',', $data);
        if (count($arr) > 1) {
            list($prefix, $contents) = $arr;
            $contents = base64_decode($contents);
        } else {
            $contents = '';
        }

        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $this->getEntityManager()->saveEntity($attachment);
        $this->getContainer()->get('fileStorageManager')->putContents($attachment, $contents);

        return array(
            'attachmentId' => $attachment->id
        );
    }

}

