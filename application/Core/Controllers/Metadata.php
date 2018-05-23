<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\Forbidden;

class Metadata extends \Core\Core\Controllers\Base
{

    public function actionRead($params, $data)
    {
        return $this->getMetadata()->getAllForFrontend();
    }

    public function getActionGet($params, $data, $request)
    {
        if (!$this->getUser()->isAdmin()) {
            throw new \Forbidden();
        }
        $key = $request->get('key');

        return $this->getMetadata()->get($key, false);
    }
}
