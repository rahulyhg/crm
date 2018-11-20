<?php


namespace Core\Core\Utils\Authentication;

use \Core\Core\Exceptions\Error;

class Core extends Base
{
    public function login($username, $password, \Core\Entities\AuthToken $authToken = null)
    {
        if ($authToken) {
            $hash = $authToken->get('hash');
        } else {
            $hash = $this->getPasswordHash()->hash($password);
        }

        $user = $this->getEntityManager()->getRepository('User')->findOne(array(
            'whereClause' => array(
                'userName' => $username,
                'password' => $hash
            )
        ));

        return $user;
    }
}

