<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;

class InboundEmail extends \Core\Core\Controllers\Record
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function actionGetFolders($params, $data, $request)
    {
        return $this->getRecordService()->getFolders(array(
            'host' => $request->get('host'),
            'port' => $request->get('port'),
            'ssl' => $request->get('ssl') === 'true',
            'username' => $request->get('username'),
            'password' => $request->get('password'),
            'id' => $request->get('id')
        ));
    }

    public function actionTestConnection($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        if (is_null($data['password'])) {
            $inboundEmail = $this->getEntityManager()->getEntity('InboundEmail', $data['id']);
            if (!$inboundEmail) {
                throw new Error();
            }
            $data['password'] = $this->getContainer()->get('crypt')->decrypt($inboundEmail->get('password'));
        }

        return $this->getRecordService()->testConnection($data);
    }

}
