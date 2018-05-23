<?php


namespace Core\Modules\Crm\Business\Distribution\Lead;

class RoundRobin
{
    protected $entityManager;

    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    public function getUser($team, $targetUserPosition = null)
    {
        $params = array();
        if (!empty($targetUserPosition)) {
            $params['additionalColumnsConditions'] = array(
                'role' => $targetUserPosition
            );
        }

        $userList = $team->get('users', $params);

        if (count($userList) == 0) {
            return false;
        }

        $userIdList = array();

        foreach ($userList as $user) {
            $userIdList[] = $user->id;
        }

        $lead = $this->getEntityManager()->getRepository('Lead')->where(array(
            'assignedUserId' => $userIdList
        ))->order('createdAt', 'DESC')->findOne();

        if (empty($lead)) {
            $num = 0;
        } else {
            $num = array_search($lead->get('assignedUserId'), $userIdList);
            if ($num === false || $num == count($userIdList) - 1) {
                $num = 0;
            } else {
                $num++;
            }
        }

        return $this->getEntityManager()->getEntity('User', $userIdList[$num]);
    }
}

