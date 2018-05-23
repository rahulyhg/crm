<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\Forbidden;

class LastViewed extends \Core\Core\Controllers\Base
{
    public function getActionIndex($params, $data, $request)
    {
        $result = $this->getServiceFactory()->create('LastViewed')->get();

        return [
            'total' => $result['total'],
            'list' => isset($result['collection']) ? $result['collection']->toArray() : $result['list']
        ];
    }
}

