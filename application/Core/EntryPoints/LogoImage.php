<?php


namespace Core\EntryPoints;

use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;
use \Core\Core\Exceptions\Error;

class LogoImage extends Image
{
    public static $authRequired = false;

    public function run()
    {
        $this->imageSizes['small-logo'] = array(181, 44);

        if (!empty($_GET['id'])) {
            $id = $_GET['id'];
        } else {
            $id = $this->getConfig()->get('companyLogoId');
        }

        if (empty($id)) {
            throw new NotFound();
        }

        $size = null;
        if (!empty($_GET['size'])) {
            $size = $_GET['size'];
        }

        $this->show($id, $size);
    }
}

