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

namespace Core\Modules\Advanced\SelectManagers;

class QuoteItem extends \Core\Core\SelectManagers\Base
{
    protected function acessOnlyOwn(&$result)
    {
        $result['whereClause'][] = array(
            'quote.assignedUserId' => $this->getUser()->id
        );
    }

    protected function accessOnlyTeam(&$result)
    {
        $teamIdList = $this->user->getLinkMultipleIdList('teams');
        if (empty($teamIdList)) {
            $result['customWhere'] .= " AND quote.assigned_user_id = ".$this->getEntityManager()->getPDO()->quote($this->getUser()->id);
            return;
        }
        $arr = [];
        if (is_array($teamIdList)) {
            foreach ($teamIdList as $teamId) {
                $arr[] = $this->getEntityManager()->getPDO()->quote($teamId);
            }
        }

        $result['customJoin'] .= " LEFT JOIN entity_team AS teamsMiddle ON teamsMiddle.entity_type = 'Quote' AND teamsMiddle.entity_id = quote.id AND teamsMiddle.deleted = 0";
        $result['customWhere'] .= "
            AND (
                teamsMiddle.team_id IN (" . implode(', ', $arr) . ")
                 OR
                quote.assigned_user_id = ".$this->getEntityManager()->getPDO()->quote($this->getUser()->id)."
            )
        ";
        $result['whereClause'][] = array(
            'quoteId!=' => null
        );
    }

    protected function accessPortalOnlyOwn(&$result)
    {
        $result['whereClause'][] = array(
            'id' => null
        );
    }

    protected function accessPortalOnlyContact(&$result)
    {
        $result['whereClause'][] = array(
            'id' => null
        );
    }

    protected function accessPortalOnlyAccount(&$result)
    {
        $result['whereClause'][] = array(
            'id' => null
        );
    }
}
