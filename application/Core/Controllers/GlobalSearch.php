<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\Error,
    \Core\Core\Exceptions\Forbidden;

class GlobalSearch extends \Core\Core\Controllers\Base
{
    public function actionSearch($params, $data, $request)
    {
        $query = $request->get('q');

        $offset = intval($request->get('offset'));
        $maxSize = intval($request->get('maxSize'));

        return $this->getService('GlobalSearch')->find($query, $offset, $maxSize);
    }
}

