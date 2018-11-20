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

namespace Core\Modules\Advanced\Core\Google\Actions;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\NotFound;

class ContactsGroup extends Base
{	
    protected function getClient()
    {
        return parent::getClient()->getContactsClient();
    }

    protected function asContactsGroupFeed($string)
    {
        return new \Core\Modules\Advanced\Core\Google\Items\ContactsGroupFeed($string);
    }

    protected function asContactsGroupEntry($string)
    {
        return new \Core\Modules\Advanced\Core\Google\Items\ContactsEntry($string);
    }

    public function getGroupList($params = array())
    {
        static $lists = array();
        $client = $this->getClient();
        $response = $client->getGroupList($params);
        if (!empty($response)) {
            try {
                $feed = $this->asContactsGroupFeed($response);
                $entries = $feed->getEntries();
                foreach ($entries as $entry) {
                    $parsedEntry = $this->asContactsGroupEntry($entry);
                    $lists[$parsedEntry->getId()] = $parsedEntry->getTitle();
                }
                $nextPageLink = $feed->getNextLink();
                if (!empty($nextPageLink)) {
                    $queryString = parse_url($nextPageLink, PHP_URL_QUERY);
                    parse_str($queryString, $queryParams);
                    $this->getGroupList($queryParams);
                }
            } catch (\Exception $e) {
                $GLOBALS['log']->error('Google Contacts. Getting List Error: '. $e->getMessage());
            }
        }
        return $lists;
    }

}
