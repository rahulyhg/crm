<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;

class EmailAccount extends \Core\Core\Controllers\Record
{
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

    protected function checkControllerAccess()
    {
        if (!$this->getAcl()->check('EmailAccountScope')) {
            throw new Forbidden();
        }
    }

    public function actionTestConnection($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        if (is_null($data['password'])) {
            $emailAccount = $this->getEntityManager()->getEntity('EmailAccount', $data['id']);
            if (!$emailAccount) {
                throw new Error();
            }

            if ($emailAccount->get('assignedUserId') != $this->getUser()->id && !$this->getUser()->isAdmin()) {
                throw new Forbidden();
            }

            $data['password'] = $this->getContainer()->get('crypt')->decrypt($emailAccount->get('password'));
        }

        return $this->getRecordService()->testConnection($data);
    }
}

