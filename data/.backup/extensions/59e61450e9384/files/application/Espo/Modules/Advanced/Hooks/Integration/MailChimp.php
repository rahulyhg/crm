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

namespace Core\Modules\Advanced\Hooks\Integration;

use Core\ORM\Entity;

class MailChimp extends \Core\Core\Hooks\Base
{
    public static $order = 20;

    protected function init()
    {
        $this->dependencies[] = 'metadata';
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    public function afterSave(Entity $entity)
    {
        if ($entity->id == 'MailChimp') {
            if ($entity->getFetched('enabled') != $entity->get('enabled')) {
                $metadata = $this->getMetadata();
                $data = array(
                    'mailChimpNotification' => array(
                        'disabled' => ! ((bool) $entity->get('enabled')),
                    ),
                );

                $metadata->set('app', 'popupNotifications', $data);

                $metadata->save();
            }
            if ($entity->get('enabled') && count($entity->get('customMergeFields'))) {
                $data = [
                    'status' => 'Pending',
                    'name' => 'CheckMergeFieldsExisting',
                ];
                $item = $this->getEntityManager()->getRepository('MailChimpQueue')->where($data)->findOne();
                if (!$item) {
                    $item = $this->getEntityManager()->getEntity('MailChimpQueue');
                    $item->set($data);
                    $this->getEntityManager()->saveEntity($item);
                }
            }
        }

    }
}

