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

namespace Core\Modules\Advanced\SelectManagers;

class Report extends \Core\Core\SelectManagers\Base
{
    protected function filterListTargets(&$result)
    {
        $result['whereClause'][] = array(
            'type=' => 'List',
            'entityType' => ['Contact', 'Lead', 'User', 'Account']
        );
    }

    protected function filterListAccounts(&$result)
    {
        $result['whereClause'][] = array(
            'type=' => 'List',
            'entityType' => 'Account'
        );
    }

    protected function filterListContacts(&$result)
    {
        $result['whereClause'][] = array(
            'type=' => 'List',
            'entityType' => 'Contact'
        );
    }

    protected function filterListLeads(&$result)
    {
        $result['whereClause'][] = array(
            'type=' => 'List',
            'entityType' => 'Lead'
        );
    }

    protected function filterListUsers(&$result)
    {
        $result['whereClause'][] = array(
            'type=' => 'List',
            'entityType' => 'User'
        );
    }

    protected function filterList(&$result)
    {
        $result['whereClause'][] = array(
            'type=' => 'List'
        );
    }

    protected function filterGrid(&$result)
    {
        $result['whereClause'][] = array(
            'type=' => 'Grid'
        );
    }

 }

