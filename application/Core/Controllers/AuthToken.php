<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\Forbidden;

class AuthToken extends \Core\Core\Controllers\Record
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function actionUpdate($params, $data, $request)
    {
        if (
            is_array($data) &&
            array_key_exists('isActive', $data) &&
            $data['isActive'] === false &&
            count(array_keys($data)) === 1)
        {
            return parent::actionUpdate($params, $data, $request);
        }
        throw new Forbidden();
    }

    public function actionMassUpdate($params, $data, $request)
    {
        if (empty($data['attributes'])) {
            throw new BadRequest();
        }

        $attributes = $data['attributes'];

        if (
            is_object($attributes) &&
            isset($attributes->isActive) &&
            $attributes->isActive === false &&
            count(array_keys(get_object_vars($attributes))) === 1
        ) {
            return parent::actionMassUpdate($params, $data, $request);
        }
        throw new Forbidden();
    }

    public function actionCreate($params, $data, $request)
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

