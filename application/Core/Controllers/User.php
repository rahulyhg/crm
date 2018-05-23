<?php


namespace Core\Controllers;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;

class User extends \Core\Core\Controllers\Record
{
    public function actionAcl($params, $data, $request)
    {
        $userId = $request->get('id');
        if (empty($userId)) {
            throw new Error();
        }

        if (!$this->getUser()->isAdmin() && $this->getUser()->id != $userId) {
            throw new Forbidden();
        }

        $user = $this->getEntityManager()->getEntity('User', $userId);
        if (empty($user)) {
            throw new NotFound();
        }

        return $this->getAclManager()->getMap($user);
    }

    public function postActionChangeOwnPassword($params, $data, $request)
    {
        if (!array_key_exists('password', $data) || !array_key_exists('currentPassword', $data)) {
            throw new BadRequest();
        }
        return $this->getService('User')->changePassword($this->getUser()->id, $data['password'], true, $data['currentPassword']);
    }

    public function postActionChangePasswordByRequest($params, $data, $request)
    {
        if (empty($data['requestId']) || empty($data['password'])) {
            throw new BadRequest();
        }

        $p = $this->getEntityManager()->getRepository('PasswordChangeRequest')->where(array(
            'requestId' => $data['requestId']
        ))->findOne();

        if (!$p) {
            throw new Forbidden();
        }
        $userId = $p->get('userId');
        if (!$userId) {
            throw new Error();
        }

        $this->getEntityManager()->removeEntity($p);

        if ($this->getService('User')->changePassword($userId, $data['password'])) {
            return array(
                'url' => $p->get('url')
            );
        }
    }

    public function postActionPasswordChangeRequest($params, $data, $request)
    {
        if (empty($data['userName']) || empty($data['emailAddress'])) {
            throw new BadRequest();
        }

        $userName = $data['userName'];
        $emailAddress = $data['emailAddress'];
        $url = null;
        if (!empty($data['url'])) {
            $url = $data['url'];
        }

        return $this->getService('User')->passwordChangeRequest($userName, $emailAddress, $url);
    }
}

