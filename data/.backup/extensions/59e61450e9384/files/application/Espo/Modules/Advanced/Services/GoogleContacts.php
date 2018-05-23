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

namespace Core\Modules\Advanced\Services;

use \Core\ORM\Entity;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;

class GoogleContacts extends \Core\Services\Record
{
    const PUSH_PORTION = 100; //100 is max allowed by API

    protected function init()
    {
        parent::init();
        $this->addDependency('language');
        $this->addDependency('container');
        $this->addDependency('acl');
    }

    protected function getLanguage()
    {
        return $this->injections['language'];
    }

    protected function getAcl()
    {
        return $this->injections['acl'];
    }

    protected function getContainer()
    {
        return $this->injections['container'];
    }

    public function usersContactsGroups(array $params = null)
    {
        $contactsGroup = new \Core\Modules\Advanced\Core\Google\Actions\ContactsGroup($this->getContainer(), $this->getEntityManager(), $this->getMetadata(), $this->getConfig());

        $contactsGroup->setUserId($this->getUser()->id);

        return $contactsGroup->getGroupList();
    }

    public function push($entityType, array $params)
    {
        $integrationEntity = $this->getEntityManager()->getEntity('Integration', 'Google');
        if (!$integrationEntity ||
            !$integrationEntity->get('enabled')) {
            throw new Forbidden();
        }

        $userId = $this->getUser()->id;
        $externalAccount = $this->getEntityManager()->getEntity('ExternalAccount', 'Google__' . $userId);
        if (!$externalAccount->get('enabled') || !$externalAccount->get('googleContactsEnabled')) {
            throw new Forbidden();
        }

        $p = array();
        $result = 0;

        if (array_key_exists('ids', $params)) {
            $ids = $params['ids'];
            $where = array(
                array(
                    'type' => 'in',
                    'field' => 'id',
                    'value' => $ids
                )
            );
        } else if (array_key_exists('where', $params)) {
            $where = $params['where'];
        } else {
            throw new BadRequest();
        }

        $p['where'] = $where;
        $selectParams = $this->getSelectManager($entityType)->getSelectParams($p, true, true);

        $total = $this->getEntityManager()->getRepository($entityType)->count($selectParams);
        if ($total && self::PUSH_PORTION) {
            $runNow = true;
            $offset = 0;
            $p['maxSize'] = self::PUSH_PORTION;
            $now = new \DateTime("NOW", new \DateTimeZone('UTC'));
            while ($offset <= $total) {
                $p['offset'] = $offset;

                $selectParams = $this->getSelectManager($entityType)->getSelectParams($p, true, true);
                $collection = $this->getEntityManager()->getRepository($entityType)->find($selectParams);
                if ($runNow) {
                    $contactsAction = new \Core\Modules\Advanced\Core\Google\Actions\Contacts($this->getContainer(), $this->getEntityManager(), $this->getMetadata(), $this->getConfig());
                    $contactsAction->setUserId($userId);
                    $result += $contactsAction->pushCoreContactsToGoogleContacts($collection, $externalAccount->get('contactsGroupsIds'));
                    $runNow = false;
                } else {
                    $ids = array();
                    foreach ($collection as $entity) {
                        $ids[] = $entity->id;
                    }
                    $data = [
                        'ids' => $ids,
                        'userId' => $userId,
                        'entityType' => $entityType,
                    ];

                    $now->modify("+1 minute");

                    $job = $this->getEntityManager()->getEntity('Job');
                    $job->set( array(
                            'method' => 'pushPortionToGoogleContacts',
                            'serviceName' => 'GoogleContacts',
                            'executeTime' => $now->format("Y-m-d H:i" . ":00"),
                            'data' => json_encode($data),
                        )
                    );
                    $this->getEntityManager()->saveEntity($job);
                }
                $offset += self::PUSH_PORTION;
            }
        }
        return $result;
    }

    public function pushPortionToGoogleContacts($data)
    {
        $integrationEntity = $this->getEntityManager()->getEntity('Integration', 'Google');

        if (!$integrationEntity ||
            !$integrationEntity->get('enabled')) {

            $GLOBALS['log']->error('Google Contacts Pushing : Integration Disabled');
            //return false;
            throw new Forbidden();
        }

        $userId = $data['userId'];
        $entityType = $data['entityType'];
        $ids = $data['ids'];

        $externalAccount = $this->getEntityManager()->getEntity('ExternalAccount', 'Google__' . $userId);
        if (!$externalAccount->get('enabled') || !$externalAccount->get('googleContactsEnabled')) {
            $GLOBALS['log']->error('Google Contacts Pushing : Integration Disabled for User ' . $userId);
            //return false;
            throw new Forbidden();
        }

        $where = array(
            array(
                'type' => 'in',
                'field' => 'id',
                'value' => $ids
            )
        );
        $selectParams = $this->getSelectManager($entityType)->getSelectParams(array('where' => $where), true, true);

        $collection = $this->getEntityManager()->getRepository($entityType)->find($selectParams);

        $contactsAction = new \Core\Modules\Advanced\Core\Google\Actions\Contacts($this->getContainer(), $this->getEntityManager(), $this->getMetadata(), $this->getConfig());
        $contactsAction->setUserId($userId);
        $successfulPushedCnt = $contactsAction->pushCoreContactsToGoogleContacts($collection, $externalAccount->get('contactsGroupsIds'));
        return $successfulPushedCnt;
    }
}
