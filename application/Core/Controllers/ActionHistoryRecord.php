<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\Forbidden;

class ActionHistoryRecord extends \Core\Core\Controllers\Record
{
    public function actionUpdate($params, $data, $request)
    {
        throw new Forbidden();
    }

    public function actionCreate($params, $data, $request)
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

    public function actionMassDelete($params, $data, $request)
    {
        throw new Forbidden();
    }
}

