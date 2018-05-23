<?php


namespace Core\EntryPoints;

use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;

class Pdf extends \Core\Core\EntryPoints\Base
{
    public static $authRequired = true;

    public function run()
    {

        if (empty($_GET['entityId']) || empty($_GET['entityType']) || empty($_GET['templateId'])) {
            throw new BadRequest();
        }
        $entityId = $_GET['entityId'];
        $entityType = $_GET['entityType'];
        $templateId = $_GET['templateId'];

        $entity = $this->getEntityManager()->getEntity($entityType, $entityId);
        $template = $this->getEntityManager()->getEntity('Template', $templateId);

        if (!$entity || !$template) {
            throw new NotFound();
        }

        $this->getContainer()->get('serviceFactory')->create('Pdf')->buildFromTemplate($entity, $template, true);

        exit;
    }
}

