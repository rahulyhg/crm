<?php


namespace Core\Hooks\Note;

use Core\ORM\Entity;

class Notifications extends \Core\Core\Hooks\Base
{
    protected $notificationService = null;

    public static $order = 14;

    protected function init()
    {
        $this->addDependency('serviceFactory');
    }

    protected function getServiceFactory()
    {
        return $this->getInjection('serviceFactory');
    }

    protected function getMentionedUserIdList($entity)
    {
        $mentionedUserList = array();
        $data = $entity->get('data');
        if (($data instanceof \stdClass) && ($data->mentions instanceof \stdClass)) {
            $mentions = get_object_vars($data->mentions);
            foreach ($mentions as $d) {
                $mentionedUserList[] = $d->id;
            }
        }
        return $mentionedUserList;
    }

    protected function getSubscriberIdList($parentType, $parentId, $isInternal = false)
    {
        $pdo = $this->getEntityManager()->getPDO();

        if (!$isInternal) {
            $sql = "
                SELECT user_id AS userId
                FROM subscription
                WHERE entity_id = " . $pdo->quote($parentId) . " AND entity_type = " . $pdo->quote($parentType) . "
            ";
        } else {
            $sql = "
                SELECT subscription.user_id AS userId
                FROM subscription
                JOIN user ON user.id = subscription.user_id
                WHERE
                    entity_id = " . $pdo->quote($parentId) . " AND entity_type = " . $pdo->quote($parentType) . " AND
                    user.is_portal_user = 0
            ";
        }
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $userIdList = [];
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            if ($this->getUser()->id != $row['userId']) {
                $userIdList[] = $row['userId'];
            }
        }
        return $userIdList;
    }

    public function afterSave(Entity $entity)
    {
        if ($entity->isNew()) {
            $parentType = $entity->get('parentType');
            $parentId = $entity->get('parentId');
            $superParentType = $entity->get('superParentType');
            $superParentId = $entity->get('superParentId');

            $userIdList = [];

            if ($parentType && $parentId) {
				$userIdList = array_merge($userIdList, $this->getSubscriberIdList($parentType, $parentId, $entity->get('isInternal')));
                if ($superParentType && $superParentId) {
                    $userIdList = array_merge($userIdList, $this->getSubscriberIdList($superParentType, $superParentId, $entity->get('isInternal')));
                }
            } else {
                $targetType = $entity->get('targetType');
                if ($targetType === 'users') {
                    $targetUserIdList = $entity->get('usersIds');
                    if (is_array($targetUserIdList)) {
                        foreach ($targetUserIdList as $userId) {
                            if ($userId === $this->getUser()->id) continue;
                            if (in_array($userId, $userIdList)) continue;
                            $userIdList[] = $userId;
                        }
                    }
                } else if ($targetType === 'teams') {
                    $targetTeamIdList = $entity->get('teamsIds');
                    if (is_array($targetTeamIdList)) {
                        foreach ($targetTeamIdList as $teamId) {
                            $team = $this->getEntityManager()->getEntity('Team', $teamId);
                            if (!$team) continue;
                            $targetUserList = $this->getEntityManager()->getRepository('Team')->findRelated($team, 'users', array(
                                'whereClause' => array(
                                    'isActive' => true
                                )
                            ));
                            foreach ($targetUserList as $user) {
                                if ($user->id === $this->getUser()->id) continue;
                                if (in_array($user->id, $userIdList)) continue;
                                $userIdList[] = $user->id;
                            }
                        }
                    }
                } else if ($targetType === 'portals') {
                    $targetPortalIdList = $entity->get('portalsIds');
                    if (is_array($targetPortalIdList)) {
                        foreach ($targetPortalIdList as $portalId) {
                            $portal = $this->getEntityManager()->getEntity('Portal', $portalId);
                            if (!$portal) continue;
                            $targetUserList = $this->getEntityManager()->getRepository('Portal')->findRelated($portal, 'users', array(
                                'whereClause' => array(
                                    'isActive' => true
                                )
                            ));
                            foreach ($targetUserList as $user) {
                                if ($user->id === $this->getUser()->id) continue;
                                if (in_array($user->id, $userIdList)) continue;
                                $userIdList[] = $user->id;
                            }
                        }
                    }
                } else if ($targetType === 'all') {
                    $targetUserList = $this->getEntityManager()->getRepository('User')->find(array(
                        'whereClause' => array(
                            'isActive' => true,
                            'isPortalUser' => false
                        )
                    ));
                    foreach ($targetUserList as $user) {
                        if ($user->id === $this->getUser()->id) continue;
                        $userIdList[] = $user->id;
                    }
                }
            }

            $userIdList = array_unique($userIdList);

            foreach ($userIdList as $i => $userId) {
                if ($entity->isUserIdNotified($userId)) {
                    unset($userIdList[$i]);
                }
            }
            $userIdList = array_values($userIdList);

            if (!empty($userIdList)) {
            	$this->getNotificationService()->notifyAboutNote($userIdList, $entity);
            }
        }
    }

    protected function getNotificationService()
    {
        if (empty($this->notificationService)) {
            $this->notificationService = $this->getServiceFactory()->create('Notification');
        }
        return $this->notificationService;
    }
}

