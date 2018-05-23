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

namespace Core\Modules\Advanced\Core\MailChimp;

use \Core\ORM\Entity;

class RecipientHelper
{
    protected $entityManager = null;
    protected $metadata = null;
    protected $language = null;
    protected $dateTime = null;
    protected $config = null;

    protected $mergeFieldsMap = [
        'ESPNM' => 'entityName',
        'ESPID' => 'entityId',
        'EMAIL' => 'emailAddress',

        'LNAME' => [
            'Account' => 'name',
            'Contact' => 'lastName',
            'Lead' => 'lastName',
            'User' => 'lastName',
        ],
        'FNAME' => [
            'Contact' => 'firstName',
            'Lead' => 'firstName',
            'User' => 'firstName',
        ],
    ];

    protected $mergeFieldsDefs = [
        'ESPNM' => [
            'type' => 'text',
            'name' => 'Core Entity Name',
            'public' => false
        ],
        'ESPID' => [
            'type' => 'text',
            'name' => 'Core Entity Id',
            'public' => false
        ]
    ];

    public function __construct($entityManager, $metadata, $config, $language, $dateTime)
    {

        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->config = $config;
        $this->dateTime = $dateTime;
        $this->language = $language;
        $this->initCustomMergeFields();
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getLanguage()
    {
        return $this->language;
    }

    protected function getDateTime()
    {
        return $this->dateTime;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    private function initCustomMergeFields()
    {
        $integration = $this->getEntityManager()->getEntity('Integration', 'MailChimp');
        if (empty($integration)) {
            throw new \Error('MailChimp Integration is disabled', 404);
        }

        $fieldList = array_keys($this->mergeFieldsDefs);

        $customMergeFields = $integration->get('customMergeFields');
        if (is_array($customMergeFields)) {
            foreach ($customMergeFields as $field) {
                $tag = $field->mergeFieldTag;
                if (!in_array($tag, $fieldList)) {
                    $fieldList[] = $tag;
                    $this->mergeFieldsMap[$tag] = (array) $field->scopes;
                }
                if (!in_array($tag, $this->mergeFieldsDefs)) {
                    $this->mergeFieldsDefs[$tag] = [
                        'name' => $field->mergeFieldName,
                        'type' => $field->mergeFieldType,
                    ];
                }
            }
        }
    }

    public function getMergeFieldList()
    {
        return array_keys($this->mergeFieldsDefs);
    }

    public function getMergeFieldDefs($field)
    {
        if (isset($this->mergeFieldsDefs[$field])) {
            $defs = $this->mergeFieldsDefs[$field];
            $defs['tag'] = $field;
            if (!isset($defs['public'])) {
                $defs['public'] = true;
            }
            return $defs;
        }
        return false;
    }

    public function getImportantFieldListForScope($scope)
    {
        $fields = ['emailAddress'];

        foreach ($this->mergeFieldsMap as $tagRule) {
            if (!empty($tagRule[$scope]) && !in_array($tagRule[$scope], $fields)){
                $fields[] = $tagRule[$scope];
            }
        }
        return $fields;
    }

    public function getTargetListRecipients(Entity $entity, $markerData = null)
    {
        $recipients = [];
        $relations = [
            'accounts' => 'account_target_list',
            'users' => 'target_list_user',
            'leads' => 'lead_target_list',
            'contacts' => 'contact_target_list',
        ];
        $listRepository = $this->getEntityManager()->getRepository('TargetList');

        foreach ($relations as $relation => $tableName) {
            $params = [];
            $whereClause = [];
            $columns = $this->getMetadata()->get("entityDefs.TargetList.links.{$relation}.additionalColumns");
            if ($columns && isset($columns->optedOut)) {
                $whereClause[$tableName .'.opted_out'] = 0;
            }

            if (is_object($markerData) && isset($markerData->$relation)) {
                $whereClause[$tableName .'.id>'] = $markerData->$relation;
            }

            if (count($whereClause)) {
                $params['whereClause'] = $whereClause;
            }

            $res = $listRepository->findRelated($entity, $relation, $params);
            $relRecipients = $res->toArray();
            foreach ($relRecipients as $recipient) {
                if (empty($recipient['emailAddress'])) {
                    continue;
                }
                $address = $this->getEntityManager()->getRepository('EmailAddress')->getByAddress($recipient['emailAddress']);
                if (empty($address) || $address->get('optOut') || $address->get('invalid')) {
                    continue;
                }
                $recipient['scope'] = $res->getEntityName();
                $recipients[] = $recipient;
            }

        }
        return $recipients;
    }

    public function getLastRelsIds()
    {
        $pdo = $this->getEntityManager()->getPDO();
        $relations = [
            'accounts' => 'account_target_list',
            'users' => 'target_list_user',
            'leads' => 'lead_target_list',
            'contacts' => 'contact_target_list',
        ];

        $result = [];

        foreach ($relations as $relation => $tableName) {
            $query = "SELECT MAX(id) FROM `$tableName`" ;
            $sth = $pdo->prepare($query);
            $sth->execute();

            $id = $sth->fetchColumn();
            if ($id) {
                $result[$relation] = $id;
            }
        }

        return $result;
    }

    public function recognizeMCMember(array $member)
    {
        $entity = false;
        if (isset($member['merge_fields']) && isset($member['merge_fields']['ESPNM']) && isset($member['merge_fields']['ESPID'])) {
            $entity = $this->getEntityManager()->getEntity($member['merge_fields']['ESPNM'], $member['merge_fields']['ESPID']);
        }
        if (empty($entity)) {
            $entity = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddress($member['email_address']);
        }

        return $entity;
    }

    public function prepareRecipientToMailChimp($entity)
    {
        if (is_object($entity)) {
            $entityArray = $entity->toArray();
            $entityArray['scope'] = $entity->getEntityType();
        } else if (is_array($entity)) {
            $entityArray = $entity;
        } else {
            return false;
        }

        $entityName = $entityArray['scope'];

        return array_merge(['entityName' => $entityName,'entityId' => $entityArray['id']], $entityArray);
    }

    public function formatSubscriber(array $params = array(), $groupId = null, $forSubscribtion = true)
    {
        if (empty($params['emailAddress'])) {
            return false;
        }

        $data = [
            'email' => $params['emailAddress'],
            'email_type' => 'html'
        ];

        if ($forSubscribtion) {
            $data['merge_fields'] = [];
            foreach ($this->mergeFieldsMap as $tag => $mergeFieldMap) {
                if (empty($tag)) {
                    continue;
                }
                $field = '';
                if (is_array($mergeFieldMap)) {
                    if (isset($mergeFieldMap[$params['entityName']])) {
                         $field = $mergeFieldMap[$params['entityName']];
                    }
                } else {
                    if (isset($params[$mergeFieldMap])) {
                        $field = $mergeFieldMap;
                    }
                }

                if ($field) {
                    $fieldType = $this->getMetadata()->get("entityDefs.{$params['entityName']}.fields.{$field}.type");
                    if (in_array($fieldType, ['link', 'linkParent'])) {
                        $field = $field . 'Name';
                    }

                    $value = null;
                    if (isset($params[$field])) {
                        $value = $params[$field];
                    }
                    $mergeFieldType = (isset($this->mergeFieldsDefs[$tag])) ? $this->mergeFieldsDefs[$tag]['type'] : 'text';

                    if ($value !== null) {
                        $method = 'convertTo' . ucfirst($mergeFieldType);
                        if (method_exists($this, $method)) {
                            $additionalData = [];
                            $additionalData['fieldType'] = $fieldType;
                            if ($fieldType == 'enum') {
                                $additionalData['scope'] = $params['entityName'];
                                $additionalData['field'] = $field;
                            }
                            if ($fieldType == 'currency') {
                                $additionalData['currency'] = $params[$field . 'Currency'];
                            }
                            $value = $this->$method($value, $additionalData);
                        }
                        $data['merge_fields'][$tag] = $value;
                    }
                }
            }
        }

        if (!empty($params['oldEmailAddress'])) {
            $data['old_email'] = $params['oldEmailAddress'];
        }
        if (!empty($groupId)) {
            $data['interests'] = [$groupId => $forSubscribtion];
        }
        return $data;
    }

    protected function formatCurrency($value, $currency = null, $showCurrency = true)
    {
        if ($value === "") {
            return $value;
        }
        $userThousandSeparator = $this->getConfig()->get('thousandSeparator');
        $userDecimalMark = $this->getConfig()->get('decimalMark');
        $currencyFormat = (int) $this->getConfig()->get('currencyFormat');

        if (!$currency) {
            $currency = $this->getConfig()->get('defaultCurrency');
        }
        if ($currencyFormat) {
            $pad = (int) $this->getConfig()->get('currencyDecimalPlaces');
            $value = number_format($value, $pad, $userDecimalMark, $userThousandSeparator);
        } else {
            $value = $this->formatFloat($value);
        }
        if ($showCurrency) {
            switch ($currencyFormat) {
                case 1:
                    $value = $value . ' ' . $currency;
                    break;
                case 2:
                    $currencySign = $this->getMetadata()->get(['app', 'currency', 'symbolMap', $currency]);
                    $value = $currencySign . $value;
                    break;
            }
        }
        return $value;
    }

    protected function formatInt($value)
    {
        if ($value === "") {
            return $value;
        }
        $userThousandSeparator = $this->getConfig()->get('thousandSeparator');
        $userDecimalMark = $this->getConfig()->get('decimalMark');
        return number_format($value, 0, $userDecimalMark, $userThousandSeparator);
    }

    protected function formatFloat($value)
    {
        if ($value === "") {
            return $value;
        }
        $userThousandSeparator = $this->getConfig()->get('thousandSeparator');
        $userDecimalMark = $this->getConfig()->get('decimalMark');
        return rtrim(rtrim(number_format($value, 8, $userDecimalMark, $userThousandSeparator), '0'), $userDecimalMark);
    }

    protected function convertToNumber($value, $additionalData = null)
    {
        return (int) $value;
    }

    protected function convertToPhone($value, $additionalData = null)
    {
        return $value;
    }

    protected function convertToUrl($value, $additionalData = null)
    {
        if (strpos($value, '.') === false) {
            return '';
        }
        if (strpos($value, 'http://') !== 0 && strpos($value, 'https://') !== 0) {
            $value = 'http://' . $value;
        }
        return $value;
    }

    protected function convertToZip($value, array $additionalData = [])
    {
        $value = (int) $value;
        $value = substr('00000' . $value, -5);
        return ($value != "00000") ? $value : '';
    }

    protected function convertToDate($value, array $additionalData = [])
    {
        if (!$value) {
            return '';
        }
        try {
            $date = new \DateTime($value);
            if ($date) {
                return $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return '';
        }
    }

    protected function convertToBirthday($value, array $additionalData = [])
    {
        if (!$value) {
            return '';
        }
        try {
            $date = new \DateTime($value);
            if ($date) {
                return $date->format('m/d');
            }
        } catch (\Exception $e) {
            return '';
        }
    }

    protected function convertToText($value, array $additionalData = [])
    {
        switch($additionalData['fieldType']) {
            case 'date':
                if (!empty($value)) {
                    $value = $this->getDateTime()->convertSystemDate($value);
                }
                break;
            case 'datetime':
                if (!empty($value)) {
                    $value = $this->getDateTime()->convertSystemDateTime($value);
                }
                break;
            case 'bool': $value = ($value) ? '1' : '0'; break;
            case 'enum':
                    $value = $this->getLanguage()->translateOption($value, $additionalData['field'], $additionalData['scope']);
                break;
            case 'int':
                $value = $this->formatInt($value);
                break;
            case 'float':
                $value = $this->formatFloat($value);
                break;
            case 'currency':
                $value = $this->formatCurrency($value, $additionalData['currency']);
                break;
            case 'currencyConverted':
                $value = $this->formatCurrency($value);
                break;
        }
        return $value;
    }

}
