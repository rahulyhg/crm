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

namespace Core\Modules\Advanced\Hooks\TargetList;

use \Core\ORM\Entity;

class MailChimp extends \Core\Core\Hooks\Base
{
    public static $order = 9;

    protected $mailChimpManager;

    protected $relationList = ['accounts', 'users', 'leads', 'contacts'];

    protected function init()
    {
        parent::init();
        $this->addDependency('language');
        $this->addDependency('dateTime');
        $this->addDependency('fileManager');
    }

    protected function getDateTime()
    {
        return $this->getInjection('dateTime');
    }

    protected function getLanguage()
    {
        return $this->getInjection('language');
    }

    protected function getFileManager()
    {
        return $this->getInjection('fileManager');
    }

    protected function getMailChimpManager()
    {
        if (!$this->mailChimpManager) {
            $this->mailChimpManager = new \Core\Modules\Advanced\Core\MailChimp\MailChimpManager($this->getEntityManager(), $this->getMetadata(), $this->getConfig(), $this->getFileManager(), $this->getLanguage(), $this->getDateTime());
        }
        return $this->mailChimpManager;
    }

    public function beforeSave(Entity $entity)
    {
        if (!$entity->isNew() && ($entity->isFieldChanged('mailChimpListId') || $entity->isFieldChanged('mcListGroupId'))) {
            $entity->set('mailChimpManualSyncRun', false);
            $entity->set('mailChimpLastSuccessfulUpdating', null);
            $this->getEntityManager()->getRepository('MailChimpLogMarker')->resetMarkers($entity->id);
        }
    }

    public function afterRelate(Entity $entity, array $options = array(), array $data = array())
    {
        $target = $data['foreignEntity'];
        if (in_array($data['relationName'], $this->relationList) &&
            $entity->get('mailChimpListId') &&
            is_object($target) &&
            $target->get('emailAddress')) {
            $this->getMailChimpManager()->addSubscribeItemToQueue($entity, $target);
        }
    }

    public function afterMassRelate(Entity $entity, array $options = array(), array $data = array())
    {
        $relationName = $data['relationName'];
        $params = $data['relationParams'];

        if (in_array($relationName, $this->relationList) && $entity->get('mailChimpListId')) {
            $relatedEntities = $this->getEntityManager()->getRepository("TargetList")->findRelated($entity, $relationName, $params);
            foreach($relatedEntities as $target) {
                if ($target->get('emailAddress')) {
                    $this->getMailChimpManager()->addSubscribeItemToQueue($entity, $target);
                }
            }
        }
    }

    public function afterUnlinkAll(Entity $entity, array $options = array(), array $data = array())
    {
        $relationMap = [
            'accounts' => 'account_target_list',
            'users' => 'target_list_user',
            'leads' => 'lead_target_list',
            'contacts' => 'contact_target_list',
        ];
        $relationName = $data['link'];

        if (isset($relationMap[$relationName])) {
            $pdo = $this->getEntityManager()->getPDO();

            $memberIdName = substr($relationName, 0, -1) . '_id';
            $tableName = $relationMap[$relationName];
            $entityType = ucfirst(substr($relationName, 0, -1));

            $sql = "SELECT $memberIdName as id FROM $tableName WHERE opted_out=0 AND deleted=1 AND target_list_id=" . $pdo->quote($entity->id);

            $sth = $pdo->prepare($sql);
            $sth->execute();

            $memberIds = $sth->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($memberIds as $member) {
                $target = $this->getEntityManager()->getEntity($entityType, $member['id']);
                if ($target && $target->get('emailAddress')) {
                    $this->getMailChimpManager()->addUnsubscribeItemToQueue($entity, $target);
                }
            }
        }
    }

    public function afterUnrelate(Entity $entity, array $options = array(), array $data = array())
    {
        $target = $data['foreignEntity'];
        if ($entity->get('mailChimpListId') &&
            is_object($target) &&
            in_array($data['relationName'], $this->relationList) &&
            $target->get('emailAddress')) {

            $this->getMailChimpManager()->addUnsubscribeItemToQueue($entity, $target);
        }
    }

    public function afterOptOut(Entity $entity, array $options = array(), array $data = array())
    {
        $target = $this->getEntityManager()->getEntity($data['targetType'], $data['targetId']);
        if (in_array($data['link'], $this->relationList) &&
            $target &&
            $entity->get('mailChimpListId') &&
            $target->get('emailAddress')) {

            $this->getMailChimpManager()->addUnsubscribeItemToQueue($entity, $target);
        }
    }

    public function afterCancelOptOut(Entity $entity, array $options = array(), array $data = array())
    {
        $target = $this->getEntityManager()->getEntity($data['targetType'], $data['targetId']);
        if (in_array($data['link'], $this->relationList) &&
            $target &&
            $entity->get('mailChimpListId') &&
            $target->get('emailAddress')) {

            $this->getMailChimpManager()->addSubscribeItemToQueue($entity, $target);
        }
    }

}

