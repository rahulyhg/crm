<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\BadRequest;

class App extends \Core\Core\Controllers\Base
{
    public function actionUser()
    {
        $preferences = $this->getPreferences()->getValues();
        unset($preferences['smtpPassword']);

        $user = $this->getUser();
        if (!$user->has('teamsIds')) {
            $user->loadLinkMultipleField('teams');
        }
        if ($user->get('isPortalUser')) {
            $user->loadAccountField();
            $user->loadLinkMultipleField('accounts');
        }

        $userData = $user->getValues();

        $emailAddressList = [];
        foreach ($user->get('emailAddresses') as $emailAddress) {
            if ($emailAddress->get('invalid')) continue;
            if ($user->get('emailAddrses') === $emailAddress->get('name')) continue;
            $emailAddressList[] = $emailAddress->get('name');
        }
        if ($user->get('emailAddrses')) {
            array_unshift($emailAddressList, $user->get('emailAddrses'));
        }
        $userData['emailAddressList'] = $emailAddressList;

        $settings = (object)[];
        foreach ($this->getConfig()->get('userItems') as $item) {
            $settings->$item = $this->getConfig()->get($item);
        }

        unset($userData['authTokenId']);
        unset($userData['password']);

        return array(
            'user' => $userData,
            'acl' => $this->getAcl()->getMap(),
            'preferences' => $preferences,
            'token' => $this->getUser()->get('token'),
            'settings' => $settings
        );
    }

    public function postActionDestroyAuthToken($params, $data)
    {
        $token = $data['token'];
        if (empty($token)) {
            throw new BadRequest();
        }

        $auth = new \Core\Core\Utils\Auth($this->getContainer());
        return $auth->destroyAuthToken($token);
    }
}

