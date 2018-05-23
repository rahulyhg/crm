<?php


namespace Core\Modules\Crm\SelectManagers;

class Account extends \Core\Core\SelectManagers\Base
{
    protected function filterPartners(&$result)
    {
        $result['whereClause'][] = array(
            'type' => 'Partner'
        );
    }

    protected function filterCustomers(&$result)
    {
        $result['whereClause'][] = array(
            'type' => 'Customer'
        );
    }

    protected function filterResellers(&$result)
    {
        $result['whereClause'][] = array(
            'type' => 'Reseller'
        );
    }

    protected function filterRecentlyCreated(&$result)
    {
        $dt = new \DateTime('now');
        $dt->modify('-7 days');

        $result['whereClause'][] = array(
            'createdAt>=' => $dt->format('Y-m-d H:i:s')
        );
    }

    protected function accessPortalOnlyAccount(&$result)
    {
        $d = array();

        $accountIdList = $this->getUser()->getLinkMultipleIdList('accounts');

        if (count($accountIdList)) {
            $result['whereClause'][] = array(
                'id' => $accountIdList
            );
        } else {
            $result['whereClause'][] = array(
                'id' => null
            );
        }
    }

 }

