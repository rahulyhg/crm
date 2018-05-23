<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;

class Job extends \Core\Core\Controllers\Record
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function actionCreate($params, $data, $request)
    {
        throw new Forbidden();
    }

    public function actionUpdate($params, $data, $request)
    {
        throw new Forbidden();
    }

    public function actionPatch($params, $data, $request)
    {
        throw new Forbidden();
    }

    public function actionListLinked($params, $data, $request)
    {
        throw new Forbidden();
    }

    public function actionMassUpdate($params, $data, $request)
    {
        throw new Forbidden();
    }

    public function actionCreateLink($params, $data, $request)
    {
        throw new Forbidden();
    }

    public function actionRemoveLink($params, $data, $request)
    {
        throw new Forbidden();
    }
}

