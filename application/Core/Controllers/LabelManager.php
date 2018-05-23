<?php


namespace Core\Controllers;

use Core\Core\Utils as Utils;
use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;

class LabelManager extends \Core\Core\Controllers\Base
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function postActionGetScopeList($params)
    {
        $labelManager = $this->getContainer()->get('injectableFactory')->createByClassName('\\Core\\Core\\Utils\\LabelManager');

        return $labelManager->getScopeList();
    }

    public function postActionGetScopeData($params, $data, $request)
    {
        if (empty($data['scope']) || empty($data['language'])) {
            throw new BadRequest();
        }
        $labelManager = $this->getContainer()->get('injectableFactory')->createByClassName('\\Core\\Core\\Utils\\LabelManager');
        return $labelManager->getScopeData($data['language'], $data['scope']);
    }

    public function postActionSaveLabels($params, $data)
    {
        if (empty($data['scope']) || empty($data['language']) || !isset($data['labels'])) {
            throw new BadRequest();
        }

        $labels = get_object_vars($data['labels']);

        $labelManager = $this->getContainer()->get('injectableFactory')->createByClassName('\\Core\\Core\\Utils\\LabelManager');
        $returnData = $labelManager->saveLabels($data['language'], $data['scope'], $labels);

        $this->getContainer()->get('dataManager')->clearCache();

        return $returnData;
    }
}
