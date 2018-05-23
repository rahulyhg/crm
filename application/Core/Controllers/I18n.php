<?php


namespace Core\Controllers;

class I18n extends \Core\Core\Controllers\Base
{
    public function actionRead($params, $data)
    {
        return $this->getContainer()->get('language')->getAll();
    }
}
