<?php
/*********************************************************************************
 * The contents of this file are subject to the Samex CRM Advanced
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

class ReportTargetListSync extends \Core\Core\Jobs\Base
{

    public function run()
    {
        $reportService = $this->getServiceFactory()->create('Report');

        $targetListList = $this->getEntityManager()->getRepository('TargetList')->where(array(
            'syncWithReportsEnabled' => true
        ))->find();

        foreach ($targetListList as $targetList) {
            try {
                $reportService->syncTargetListWithReports($targetList);
            } catch (\Exceptions $e) {
                $GLOBALS['log']->error('ReportTargetListSync: [' . $e->getCode() . '] ' .$e->getMessage());
            }
        }

        return true;
    }
}
