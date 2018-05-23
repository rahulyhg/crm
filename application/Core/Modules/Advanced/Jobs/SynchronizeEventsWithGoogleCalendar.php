<?php
/*********************************************************************************
 * The contents of this file are subject to the CoreCRM Advanced
 * Agreement ("License") which can be viewed at
 * http://www.espocrm.com/advanced-pack-agreement.
 * By installing or using this file, You have unconditionally agreed to the
 * terms and conditions of the License, and You may not use this file except in
 * compliance with the License.  Under the terms of the license, You shall not,
 * sublicense, resell, rent, lease, distribute, or otherwise  transfer rights
 * or usage to the software.
 * 
 * License ID: bcac485dee9efd0f36cf6842ad5b69b4
 ***********************************************************************************/

namespace Core\Modules\Advanced\Jobs;

use \Core\Core\Exceptions;

class SynchronizeEventsWithGoogleCalendar extends \Core\Core\Jobs\Base
{

    public function run()
    {
        $integrationEntity = $this->getEntityManager()->getEntity('Integration', 'Google');

        if ($integrationEntity && $integrationEntity->get('enabled')) {

            $service = $this->getServiceFactory()->create('GoogleCalendar'); 
            $collection = $this->getEntityManager()->getRepository('GoogleCalendarUser')->where(array('active' => true))->find(array('orderBy' => 'lastLooked'));

            foreach ($collection as $calendar) {
                try {
                    $service->syncCalendar($calendar);
                } catch (\Exception $e) {
                    $GLOBALS['log']->error('GoogleCalendarERROR: Run Sync Error: ' . $e->getMessage());
                }
            }
        }

        return true;
    }
}
