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

namespace Core\Modules\Advanced\Core\Workflow;

use Core\ORM\Entity;

class Helper
{
    private $container;

    private $streamService;

    private $entityHelper;

    public function __construct(\Core\Core\Container $container)
    {
        $this->container = $container;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getEntityManager()
    {
        return $this->container->get('entityManager');
    }

    protected function getUser()
    {
        return $this->container->get('user');
    }

    protected function getServiceFactory()
    {
        return $this->container->get('serviceFactory');
    }

    protected function getStreamService()
    {
        if (empty($this->streamService)) {
            $this->streamService = $this->getServiceFactory()->create('Stream');
        }

        return $this->streamService;
    }

    public function getEntityHelper()
    {
        if (!isset($this->entityHelper)) {
            $this->entityHelper = new EntityHelper($this->container);
        }

        return $this->entityHelper;
    }

    /**
     * Get followers users ids
     *
     * @param  Entity $entity
     *
     * @return array
     */
    public function getFollowerUserIds(Entity $entity)
    {
        $users = $this->getStreamService()->getEntityFollowers($entity);

        return isset($users['idList']) ? $users['idList'] : array();
    }

    /**
     * Get followers users ids excluding AssignedUserId
     *
     * @param  Entity $entity
     *
     * @return array
     */
    public function getFollowerUserIdsExcludingAssignedUser(Entity $entity)
    {
        $userIds = $this->getFollowerUserIds($entity);

        if ($entity->hasField('assignedUserId')) {
            $assignedUserId = $entity->get('assignedUserId');
            $userIds = array_diff($userIds, array($assignedUserId));
        }

        return $userIds;
    }

    /**
     * Get user ids for team ids
     *
     * @param  array  $teamIds
     *
     * @return array
     */
    public function getUserIdsByTeamIds(array $teamIds)
    {
        $userIds = array();

        if (!empty($teamIds)) {
            $pdo = $this->getEntityManager()->getPDO();

            $sql = "
                SELECT team_user.user_id
                FROM team_user
                WHERE
                    team_user.team_id IN ('".implode("', '", $teamIds)."') AND
                    team_user.deleted = 0
            ";

            $sth = $pdo->prepare($sql);
            $sth->execute();
            if ($rows = $sth->fetchAll()) {
                foreach ($rows as $row) {
                    $userIds[] = $row['user_id'];
                }
            }
        }

        return $userIds;
    }

    /**
     * Get email addresses for an entity with specified ids
     *
     * @param  string $entityType
     * @param  array  $entityIds
     *
     * @return array
     */
    public function getEmailAddressesForEntity($entityType, array $entityIds)
    {
        $data = array();

        if (!empty($entityIds)) {
            $pdo = $this->getEntityManager()->getPDO();

            $sql = "
                SELECT email_address.name
                FROM entity_email_address
                JOIN email_address ON email_address.id = entity_email_address.email_address_id AND email_address.deleted = 0
                WHERE
                    entity_email_address.entity_id IN ('".implode("', '", $entityIds)."') AND
                    entity_email_address.entity_type = '".$entityType."' AND
                    entity_email_address.deleted = 0 AND
                    entity_email_address.primary = 1
            ";

            $sth = $pdo->prepare($sql);
            $sth->execute();
            if ($rows = $sth->fetchAll()) {
                foreach ($rows as $row) {
                    $data[] = $row['name'];
                }
            }
        }

        return $data;
    }

    /**
     * Get primary email addresses for user list
     *
     * @param  array  $userList
     *
     * @return array
     */
    public function getUsersEmailAddress(array $userList)
    {
        return $this->getEmailAddressesForEntity('User', $userList);
    }
}