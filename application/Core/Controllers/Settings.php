<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;

class Settings extends \Core\Core\Controllers\Base
{
    protected function getConfigData()
    {
        if ($this->getUser()->id == 'system') {
            $data = $this->getConfig()->getData();
        } else {
            $data = $this->getConfig()->getData($this->getUser()->isAdmin());
        }

        $fieldDefs = $this->getMetadata()->get('entityDefs.Settings.fields');

        foreach ($fieldDefs as $field => $d) {
            if ($d['type'] == 'password') {
                unset($data[$field]);
            }
        }

        $data['jsLibs'] = $this->getMetadata()->get('app.jsLibs');

        return $data;
    }

    public function actionRead($params, $data)
    {
        return $this->getConfigData();
    }

    public function actionUpdate($params, $data, $request)
    {
        return $this->actionPatch($params, $data, $request);
    }

    public function actionPatch($params, $data, $request)
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }

        if (!$request->isPut() && !$request->isPatch()) {
            throw new BadRequest();
        }

        if (
            (isset($data['useCache']) && $data['useCache'] != $this->getConfig()->get('useCache'))
            ||
            (isset($data['aclStrictMode']) && $data['aclStrictMode'] !== $this->getConfig()->get('aclStrictMode'))
        ) {
            $this->getContainer()->get('dataManager')->clearCache();
        }

        $this->getConfig()->setData($data, $this->getUser()->isAdmin());
        $result = $this->getConfig()->save();
        if ($result === false) {
            throw new Error('Cannot save settings');
        }

        /** Rebuild for Currency Settings */
        if (isset($data['baseCurrency']) || isset($data['currencyRates'])) {
            $this->getContainer()->get('dataManager')->rebuildDatabase(array());
        }
        /** END Rebuild for Currency Settings */

        return $this->getConfigData();
    }

    public function postActionTestLdapConnection($params, $data)
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }

        if (!isset($data['password'])) {
            $data['password'] = $this->getConfig()->get('ldapPassword');
        }

        $ldapUtils = new \Core\Core\Utils\Authentication\LDAP\Utils();
        $options = $ldapUtils->normalizeOptions($data);

        $ldapClient = new \Core\Core\Utils\Authentication\LDAP\Client($options);
        $ldapClient->bind(); //an exception if no connection

        return true;
    }
}
