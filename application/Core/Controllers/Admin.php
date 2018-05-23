<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;

class Admin extends \Core\Core\Controllers\Base
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function postActionRebuild($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }
        $result = $this->getContainer()->get('dataManager')->rebuild();

        return $result;
    }

    public function postActionClearCache($params, $data)
    {
        $result = $this->getContainer()->get('dataManager')->clearCache();
        return $result;
    }

    public function actionJobs()
    {
        $scheduledJob = $this->getContainer()->get('scheduledJob');

        return $scheduledJob->getAvailableList();
    }

    public function postActionUploadUpgradePackage($params, $data)
    {
        if ($this->getConfig()->get('restrictedMode')) {
            if (!$this->getUser()->get('isSuperAdmin')) {
                throw new Forbidden();
            }
        }
        $upgradeManager = new \Core\Core\UpgradeManager($this->getContainer());

        $upgradeId = $upgradeManager->upload($data);
        $manifest = $upgradeManager->getManifest();

        return array(
            'id' => $upgradeId,
            'version' => $manifest['version'],
        );
    }

    public function postActionRunUpgrade($params, $data)
    {
        if ($this->getConfig()->get('restrictedMode')) {
            if (!$this->getUser()->get('isSuperAdmin')) {
                throw new Forbidden();
            }
        }

        $upgradeManager = new \Core\Core\UpgradeManager($this->getContainer());
        $upgradeManager->install($data);

        return true;
    }

    public function actionCronMessage($params, $data)
    {
        return $this->getContainer()->get('scheduledJob')->getSetupMessage();
    }

}

