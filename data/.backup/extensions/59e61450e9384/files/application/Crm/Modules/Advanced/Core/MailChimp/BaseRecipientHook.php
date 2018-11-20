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

use \Core\ORM\Entity;

class BaseRecipientHook extends \Core\Core\Hooks\Base
{
    public static $order = 15;

    protected $mailChimpManager;

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
        $integration = $this->getEntityManager()->getEntity("Integration", "MailChimp");

        if (empty($integration) || !$integration->get('enabled')) {
            return;
        }
        $recipientHelper = new \Core\Modules\Advanced\Core\MailChimp\RecipientHelper($this->getEntityManager(), $this->getMetadata(), $this->getConfig(), $this->getLanguage(), $this->getDateTime());

        $checkFields = $recipientHelper->getImportantFieldListForScope($entity->getEntityType());
        if (!$entity->isNew() && $entity->get('emailAddress')) {
            $modified = false;
            foreach ($checkFields as $field) {
                if ($entity->hasField($field)) {

                    $fieldType = $this->getMetadata()->get("entityDefs.{$entity->getEntityType()}.fields.{$field}.type");
                    if (in_array($fieldType, ['email', 'phone'])) {
                        $field .= 'Data';
                    }
                    if (in_array($fieldType, ['link', 'linkParent'])) {
                        $field .= 'Id';
                    }

                    if ($entity->isFieldChanged($field)) {
                        $modified = true;
                        break;
                    }
                }
            }

            if (!$modified) {
                return;
            }

            $entity->loadLinkMultipleField('targetLists');
            $targetListsIds = $entity->get('targetListsIds');

            if (empty($targetListsIds)) {
                return;
            }

            $oldEntity = $this->getEntityManager()->getEntity($entity->getEntityType(), $entity->id);
            $oldEmailAddress = $oldEntity->get('emailAddress');
            $mcManager = $this->getMailChimpManager();

            foreach ($targetListsIds as $targetListId) {
                $targetList = $this->getEntityManager()->getEntity('TargetList', $targetListId);
                if ($targetList) {
                    if ($targetList->get('mailChimpListId')) {
                        if ($oldEmailAddress) {
                            $mcManager->addUpdateMemberItemToQueue($targetList, $entity, $oldEmailAddress);
                        } else {
                            $mcManager->addSubscribeItemToQueue($targetList, $entity);
                        }
                    }
                }
            }
        }
    }

    public function afterSave(Entity $entity)
    {
        $integration = $this->getEntityManager()->getEntity("Integration", "MailChimp");

        if (empty($integration) || !$integration->get('enabled')) {
            return;
        }

        if ($entity->isNew() && $entity->get('emailAddress')) {
            $targetListId = $entity->get('targetListId');

            if ($targetListId) {
                $targetList = $this->getEntityManager()->getEntity('TargetList', $targetListId);
                if ($targetList && $targetList->get('mailChimpListId')) {
                    $this->getMailChimpManager()->addSubscribeItemToQueue($targetList, $entity);
                }
            }
        }
    }

    public function beforeRemove(Entity $entity)
    {
        $integration = $this->getEntityManager()->getEntity("Integration", "MailChimp");
        if (empty($integration) || !$integration->get('enabled')) {
            return;
        }

        if ($entity->get('emailAddress')) {

            $entity->loadLinkMultipleField('targetLists');
            $targetListsIds = $entity->get('targetListsIds');

            if ($targetListsIds) {
                $mcManager = $this->getMailChimpManager();

                foreach ($targetListsIds as $targetListId) {
                    $targetList = $this->getEntityManager()->getEntity('TargetList', $targetListId);
                    if ($targetList) {
                        if ($targetList->get('mailChimpListId')) {
                            $mcManager->addUnsubscribeItemToQueue($targetList, $entity);
                        }
                    }
                }
            }
        }
    }

    public function afterRelate(Entity $entity, array $options = array(), array $data = array())
    {
        $relationName = $data['relationName'];
        $foreignEntity = $data['foreignEntity'];

        if ($relationName == 'targetLists' &&
            $foreignEntity->get('mailChimpListId') &&
            $entity->get('emailAddress')) {
           $this->getMailChimpManager()->addSubscribeItemToQueue($foreignEntity, $entity);
        }
    }

    public function afterUnrelate(Entity $entity, array $options = array(), array $data = array())
    {
        $relationName = $data['relationName'];
        $foreignEntity = $data['foreignEntity'];
        if ($relationName == 'targetLists' && $foreignEntity->get('mailChimpListId') && $entity->get('emailAddress')) {
            $this->getMailChimpManager()->addUnsubscribeItemToQueue($foreignEntity, $entity);
        }
    }

}

