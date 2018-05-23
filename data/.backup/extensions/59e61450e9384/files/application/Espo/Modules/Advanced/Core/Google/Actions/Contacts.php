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

namespace Core\Modules\Advanced\Core\Google\Actions;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\NotFound;

class Contacts extends Base
{	
    protected $helper;

    protected $contactFieldPairs = [
        'name' => 'name',
        'description' => 'content',
        'emailAddress' => 'email',
        'phoneNumber' => 'phoneNumber',
        'accountName' => 'organization',
    ];

    protected function getClient()
    {
        return parent::getClient()->getContactsClient();
    }

    protected function asContactFeed($string)
    {
        return new \Core\Modules\Advanced\Core\Google\Items\ContactsFeed($string);
    }

    protected function asContactsBatchEntry($string = null)
    {
        return new \Core\Modules\Advanced\Core\Google\Items\ContactsBatchEntry($string);
    }

    protected function asContactsBatchFeed($string = null)
    {
        return new \Core\Modules\Advanced\Core\Google\Items\ContactsBatchFeed($string);
    }

    public function getUserEmail()
    {
        $client = $this->getClient();
        $response = $client->getUserData();
        try {
            $feed = $this->asContactFeed($response);
            return $feed->getId();
        } catch (\Exception $e) {
            $GLOBALS['log']->error('Getting Google User Email: '. $e->getMessage());
        }
        return false;
    }

    public function pushCoreContactsToGoogleContacts($collection, $groupIds = array())
    {
        $client = $this->getClient();
        $successfulCnt = 0;
        if (!count($collection)) {
            return false;
        }

        $feed = $this->asContactsBatchFeed();

        $this->helper = new \StdClass();

        $this->helper->groupIds = (is_array($groupIds)) ? $groupIds : array();
        $this->helper->collectionEntityType = $collection->getEntityName();
        $forbiddenFields = $this->getAcl()->getScopeForbiddenFieldList($this->helper->collectionEntityType);
        $this->helper->forbiddenFields = (is_array($forbiddenFields)) ? $forbiddenFields : array();
        $this->helper->userEmail = $this->getUserEmail();

        $storedContactPairs = $this->loadStoredContactsPairs($this->helper->collectionEntityType);

        foreach ($collection as $entity) {
            if (isset($storedContactPairs[$entity->id])) {
                $this->batchQuery($feed, $entity->id, $storedContactPairs[$entity->id]);
            } else {
                $this->batchInsert($feed, $entity);
            }
        }

        $response = $client->batch($feed->asXML());
        $resultFeed = $this->asContactsBatchFeed($response);
        $responseEntries = $resultFeed->getEntries();

        $feed = $this->asContactsBatchFeed();

        foreach ($responseEntries as $entry) {
            $batchEntry = $this->asContactsBatchEntry($entry);
            if ($batchEntry->getOperationType() == 'query') {

                $entity = $this->getEntityManager()->getEntity($this->helper->collectionEntityType, $batchEntry->getBatchId());

                if ($batchEntry->getStatusCode() == 200) {

                    $status = $entry->getElementsByTagName('status')->item(0);

                    if ($status) {
                        $entry->removeChild($status);
                    }

                    $operation = $entry->getElementsByTagName('operation')->item(0);

                    if ($operation) {
                        $entry->removeChild($operation);
                    }

                    $updated = $entry->getElementsByTagName('updated')->item(0);
                    if ($updated) {
                        $entry->removeChild($updated);
                    }

                    $this->batchUpdate($feed, $entry, $entity);

                }

                if ($batchEntry->getStatusCode() == 404) {

                    $storedPairEntity = $this->getEntityManager()->getRepository('GoogleContactsPair')->where(
                        [
                            'googleAccountEmail' => $this->helper->userEmail,
                            'parentType' => $this->helper->collectionEntityType,
                            'parentId' => $batchEntry->getBatchId(),
                            'googleContactId' => $batchEntry->getShortId()
                        ])->findOne();

                    if ($storedPairEntity) {
                        $this->getEntityManager()->removeEntity($storedPairEntity);
                    }
                    $this->batchInsert($feed, $entity);
                }
            }
            if ($batchEntry->getOperationType() == 'insert' && $batchEntry->getStatusCode() >= 200 && $batchEntry->getStatusCode() < 300) {
                $storedPairEntity = $this->getEntityManager()->getEntity('GoogleContactsPair');
                $storedPairEntity->set('googleAccountEmail', $this->helper->userEmail);
                $storedPairEntity->set('parentType', $this->helper->collectionEntityType);
                $storedPairEntity->set('parentId', $batchEntry->getBatchId());
                $storedPairEntity->set('googleContactId', $batchEntry->getShortId());
                $this->getEntityManager()->saveEntity($storedPairEntity);

                $successfulCnt++;
            }
        }
        if ($feed->getEntries()->length) {

            $response = $client->batch($feed->asXML());
            $resultFeed = $this->asContactsBatchFeed($response);
            $responseEntries = $resultFeed->getEntries();

            foreach ($responseEntries as $entry) {
                $batchEntry = $this->asContactsBatchEntry($entry);
                if ($batchEntry->getStatusCode() >= 200 && $batchEntry->getStatusCode() < 300) {
                    $successfulCnt++;
                    if ($batchEntry->getOperationType() == 'insert') {
                        $storedPairEntity = $this->getEntityManager()->getEntity('GoogleContactsPair');
                        $storedPairEntity->set('googleAccountEmail', $this->helper->userEmail);
                        $storedPairEntity->set('parentType', $this->helper->collectionEntityType);
                        $storedPairEntity->set('parentId', $batchEntry->getBatchId());
                        $storedPairEntity->set('googleContactId', $batchEntry->getShortId());
                        $this->getEntityManager()->saveEntity($storedPairEntity);
                    }
                }
            }
        }
        return $successfulCnt;
    }

    protected function batchInsert(& $feed, $entity)
    {
        $feed->addEntry();
        $feed->addOperation('insert');
        $feed->addField('batchId', $entity->id);
        foreach ($this->contactFieldPairs as $field => $googleField) {
            if (in_array($field, $this->helper->forbiddenFields)) {
                continue;
            }

            if ($entity->hasField($field) && $entity->get($field)) {
                $attributes = array();
                if ($field == 'phoneNumber') {
                    $fieldData = $this->getEntityManager()->getRepository(ucfirst($field))->getPhoneNumberData($entity);
                    if ($fieldData) {
                        foreach ($fieldData as $item) {
                            $attributes = $this->preparePhoneNumber($item);
                            $feed->addField($googleField, $item->$field, $attributes);
                        }
                    }
                } else if ($field == 'emailAddress') {
                    $fieldData = $this->getEntityManager()->getRepository(ucfirst($field))->getEmailAddressData($entity);
                    if ($fieldData) {
                        foreach ($fieldData as $item) {
                            if ($item->optOut || $item->invalid) {
                                continue;
                            }
                            $attributes = array();
                            $attributes['primary'] = $item->primary;
                            $feed->addField($googleField, $item->$field, $attributes);
                        }
                    }
                } else if ($field == "name") {
                    if ($entity->hasField('firstName')) {
                        $attributes['firstName'] = $entity->get('firstName');
                    }
                    if ($entity->hasField('lastName')) {
                        $attributes['lastName'] = $entity->get('lastName');
                    }
                    $feed->addField($googleField, $entity->get($field), $attributes);
                    $feed->addField('title', $entity->get($field), []);
                } else {
                    $feed->addField($googleField, $entity->get($field), $attributes);
                }

            }
        }
        if (!empty($this->helper->groupIds) && is_array($this->helper->groupIds)) {
            foreach ($this->helper->groupIds as $groupId) {
                $feed->addField('group', $groupId);
            }
        }
    }

    protected function batchQuery(& $feed, $entityId, $entryId)
    {
        $feed->addEntry();
        $feed->addOperation('query');
        $feed->addField('batchId', $entityId);
        $feed->addField('id', $entryId);
    }

    protected function batchUpdate(& $feed, $entry, $entity)
    {

        $feed->addEntry($entry);
        $feed->addOperation('update');

        foreach ($this->contactFieldPairs as $field => $googleField) {
            if (in_array($field, $this->helper->forbiddenFields)) {
                continue;
            }

            if ($entity->hasField($field) && $entity->get($field)) {
                $attributes = array();
                if ($field == 'phoneNumber') {
                    $fieldData = $this->getEntityManager()->getRepository('PhoneNumber')->getPhoneNumberData($entity);
                    if ($fieldData) {
                        foreach ($fieldData as $item) {
                            $attributes = $this->preparePhoneNumber($item);
                            $feed->updateField($googleField, $item->$field, $attributes);
                        }
                    }
                } else if ($field == 'emailAddress') {
                    $fieldData = $this->getEntityManager()->getRepository('EmailAddress')->getEmailAddressData($entity);
                    if ($fieldData) {
                        foreach ($fieldData as $item) {
                            if ($item->optOut || $item->invalid) {
                                continue;
                            }
                            $attributes = array();
                            $attributes['primary'] = $item->primary;
                            $feed->updateField($googleField, $item->$field, $attributes);
                        }
                    }
                } else if ($field == "name") {
                    if ($entity->hasField('firstName')) {
                        $attributes['firstName'] = $entity->get('firstName');
                    }
                    if ($entity->hasField('lastName')) {
                        $attributes['lastName'] = $entity->get('lastName');
                    }
                    $feed->updateField($googleField, $entity->get($field), $attributes);
                    $feed->updateField('title', $entity->get($field), []);
                } else {
                    $feed->updateField($googleField, $entity->get($field), $attributes);
                }

            }
        }
        if (!empty($this->helper->groupIds) && is_array($this->helper->groupIds)) {
            foreach ($this->helper->groupIds as $groupId) {
                $feed->updateField('group', $groupId);
            }
        }
    }

    protected function preparePhoneNumber($phoneNumber)
    {
        $attributes = array();
        $phoneTypeEq = [
            'Mobile' => 'mobile',
            'Office' => 'work',
            'Home' => 'home',
            'Fax' => 'fax',
            'Other' =>'other'];
        $defaultType = 'other';
        $attributes['primary'] = $phoneNumber->primary;
        $attributes['type'] = (isset($phoneTypeEq[$phoneNumber->type])) ? $phoneTypeEq[$phoneNumber->type] : $defaultType;
        return $attributes;
    }

    protected function loadStoredContactsPairs($entityType)
    {
        $result = [];
        $pairs = $this->getEntityManager()->getRepository('GoogleContactsPair')->where(['googleAccountEmail' => $this->helper->userEmail, 'parentType' => $entityType])->find();
        foreach ($pairs as $pair) {
            $result[$pair->get('parentId')] = $pair->get("googleContactId");
        }
        return $result;
    }

}
