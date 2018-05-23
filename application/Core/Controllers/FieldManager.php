<?php


namespace Core\Controllers;

use Core\Core\Exceptions\Error;
use Core\Core\Exceptions\Forbidden;
use Core\Core\Exceptions\NotFound;
use Core\Core\Exceptions\BadRequest;

class FieldManager extends \Core\Core\Controllers\Base
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function actionRead($params, $data)
    {
        if (empty($params['scope']) || empty($params['name'])) {
            throw new BadRequest();
        }

        $data = $this->getContainer()->get('fieldManager')->read($params['scope'], $params['name']);

        if (!isset($data)) {
            throw new BadRequest();
        }

        return $data;
    }

    public function postActionCreate($params, $data)
    {
        if (empty($params['scope']) || empty($data['name'])) {
            throw new BadRequest();
        }

        $fieldManager = $this->getContainer()->get('fieldManager');
        $fieldManager->create($params['scope'], $data['name'], $data);

        try {
            $this->getContainer()->get('dataManager')->rebuild($params['scope']);
        } catch (Error $e) {
            $fieldManager->delete($params['scope'], $data['name']);
            throw new Error($e->getMessage());
        }

        return $fieldManager->read($params['scope'], $data['name']);
    }

    public function putActionUpdate($params, $data)
    {
        if (empty($params['scope']) || empty($params['name'])) {
            throw new BadRequest();
        }

        $fieldManager = $this->getContainer()->get('fieldManager');
        $fieldManager->update($params['scope'], $params['name'], $data);

        if ($fieldManager->isChanged()) {
            $this->getContainer()->get('dataManager')->rebuild($params['scope']);
        } else {
            $this->getContainer()->get('dataManager')->clearCache();
        }

        return $fieldManager->read($params['scope'], $params['name']);
    }

    public function deleteActionDelete($params, $data)
    {
        if (empty($params['scope']) || empty($params['name'])) {
            throw new BadRequest();
        }

        $result = $this->getContainer()->get('fieldManager')->delete($params['scope'], $params['name']);

        $this->getContainer()->get('dataManager')->rebuildMetadata();

        return $result;
    }

    public function postActionResetToDefault($params, $data)
    {
        if (empty($data['scope']) || empty($data['name'])) {
            throw new BadRequest();
        }

        $this->getContainer()->get('fieldManager')->resetToDefault($data['scope'], $data['name']);

        $this->getContainer()->get('dataManager')->rebuildMetadata();

        return true;
    }
}

