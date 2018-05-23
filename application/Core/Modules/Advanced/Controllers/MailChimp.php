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

namespace Core\Modules\Advanced\Controllers;

use \Core\Core\Exceptions\BadRequest;

class MailChimp extends \Core\Core\Controllers\Record
{

    public function actionRead($params, $data, $request)
    {
        if (!$this->getAcl()->check('MailChimp')) {
            throw new Forbidden();
        }
        $id = $params['id'];
        return $this->getService('MailChimp')->loadRelations($id);
    }

    public function actionUpdate($params, $data, $request)
    {
        if (!$this->getAcl()->check('MailChimp')) {
            throw new Forbidden();
        }
        return $this->getService('MailChimp')->saveRelation($params, $data);
    }

    public function actionScheduleSync($params, $data, $request)
    {
        if (!$this->getAcl()->check('MailChimp')) {
            throw new Forbidden();
        }

        $entity = $params['entity'];
        $id = $params['id'];

        return $this->getRecordService()->scheduleSync($entity, $id);
    }

    public function actionCheckSynchronization($params, $data, $request)
    {
        return $this->getEntityManager()->getRepository('MailChimp')->checkManualSyncs();
    }
}
