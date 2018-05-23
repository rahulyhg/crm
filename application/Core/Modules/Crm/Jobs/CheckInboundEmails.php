<?php


namespace Core\Modules\Crm\Jobs;

use \Core\Core\Exceptions;

class CheckInboundEmails extends \Core\Core\Jobs\Base
{
    public function run()
    {
        $service = $this->getServiceFactory()->create('InboundEmail');
        $collection = $this->getEntityManager()->getRepository('InboundEmail')->where(array('status' => 'Active'))->find();
        foreach ($collection as $entity) {
            try {
                $service->fetchFromMailServer($entity);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('Job CheckInboundEmails '.$entity->id.': [' . $e->getCode() . '] ' .$e->getMessage());
            }
        }

        return true;
    }
}

