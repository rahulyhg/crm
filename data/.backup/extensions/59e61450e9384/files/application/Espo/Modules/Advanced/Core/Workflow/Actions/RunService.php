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

namespace Core\Modules\Advanced\Core\Workflow\Actions;

use Core\ORM\Entity;
use Core\Core\Exceptions\NotFound;
use Core\Core\Exceptions\Error;
use Core\Core\Utils\Json;

class RunService extends Base
{
    /**
     * Main run method
     *
     * @param  Entity $entity
     * @param  array $actionData
     * @return string
     */
    protected function run(Entity $entity, array $actionData)
    {
        $serviceFactory = $this->getServiceFactory();

        if (empty($actionData['methodName'])) {
            throw new Error();
        }

        $name = $actionData['methodName'];

        $serviceName = $this->getMetadata()->get(['entityDefs', 'Workflow', 'serviceActions', $entity->getEntityType(), $name, 'serviceName']);
        $methodName = $this->getMetadata()->get(['entityDefs', 'Workflow', 'serviceActions', $entity->getEntityType(), $name, 'methodName']);


        if (!$serviceName || !$methodName) {
            $methodName = $name;
            $serviceName = $entity->getEntityType();
        }

        if (!$serviceFactory->checkExists($serviceName)) {
            throw new Error();
        }

        $service = $serviceFactory->create($serviceName);

        if (!method_exists($service, $methodName)) {
            throw new Error();
        }

        $data = null;
        if (!empty($actionData['additionalParameters'])) {
            $data = Json::decode($actionData['additionalParameters'], true);
        }

        $service->$methodName($this->getWorkflowId(), $entity, $data);

        return true;
    }
}