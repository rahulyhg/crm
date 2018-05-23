<?php


namespace Core\EntryPoints;

use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;

class Portal extends \Core\Core\EntryPoints\Base
{
    public static $authRequired = false;

    public function run($data = array())
    {
        if (!empty($_GET['id'])) {
            $id = $_GET['id'];
        } else if (!empty($data['id'])) {
            $id = $data['id'];
        } else {
            $url = !empty($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI'];

            $id = explode('/', $url)[count(explode('/', $_SERVER['SCRIPT_NAME'])) - 1];
            if (!$id) {
                $id = $this->getConfig()->get('defaultPortalId');
            }
            if (!$id) {
                throw new NotFound();
            }
        }

        $application = new \Core\Core\Portal\Application($id);
        $application->setBasePath($this->getContainer()->get('clientManager')->getBasePath());
        $application->runClient();
    }
}
