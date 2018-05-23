<?php


namespace Core\Modules\Crm\SelectManagers;

class Contact extends \Core\Core\SelectManagers\Base
{
    protected function filterPortalUsers(&$result)
    {
        $result['customJoin'] .= " JOIN user AS portalUser ON portalUser.contact_id = contact.id AND portalUser.deleted = 0 ";
    }

    protected function filterNotPortalUsers(&$result)
    {
        $result['customJoin'] .= " LEFT JOIN user AS portalUser ON portalUser.contact_id = contact.id AND portalUser.deleted = 0 ";
        $this->addAndWhere(array(
            'portalUser.id' => null
        ), $result);
    }

    protected function accessPortalOnlyContact(&$result)
    {
        $d = array();

        $contactId = $this->getUser()->get('contactId');

        if ($contactId) {
            $result['whereClause'][] = array(
                'id' => $contactId
            );
        } else {
            $result['whereClause'][] = array(
                'id' => null
            );
        }
    }

    protected function filterAccountActive(&$result)
    {
        if (!array_key_exists('additionalColumnsConditions', $result)) {
            $result['additionalColumnsConditions'] = array();
        }
        $result['additionalColumnsConditions']['isInactive'] = false;
    }

 }

