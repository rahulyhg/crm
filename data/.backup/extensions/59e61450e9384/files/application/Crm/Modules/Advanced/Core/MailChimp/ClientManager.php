<?php
/*********************************************************************************
 * The contents of this file are subject to the CRM Advanced
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

namespace Core\Modules\Advanced\Core\MailChimp;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\NotFound;

use \Core\Core\ExternalAccount\OAuth2\Client;

class ClientManager extends \Core\Core\ExternalAccount\ClientManager
{
    protected function createMailChimp($integration, $userId = null)
    {
        $integrationEntity = $this->getEntityManager()->getEntity('Integration', $integration);

        $className = $this->getMetadata()->get("integrations.{$integration}.clientClassName");

        if (!$integrationEntity->get('enabled')) {
            return null;
        }

        $mcClient = new \Core\Modules\Advanced\Core\MailChimp\ExternalAccount\MailChimp\Client();
        $params = array();

        $integrationParams = $this->getMetadata()->get("integrations.{$integration}.params");

        if (is_array($integrationParams)) {
            $params = $integrationParams;
        }

        $integrationFields = $this->getMetadata()->get("integrations.{$integration}.fields");

        if (is_array($integrationFields)) {
            foreach ($integrationFields as $field => $fieldParams) {
                $params[$field] = $integrationEntity->get($field);
            }
        }
        $client = new $className($mcClient, $params, $this);

        $this->addToClientMap($client, $integrationEntity, null, null);

        return $client;
    }
}
