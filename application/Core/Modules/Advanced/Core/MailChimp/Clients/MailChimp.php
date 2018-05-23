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

namespace Core\Modules\Advanced\Core\MailChimp\Clients;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\NotFound;

use Core\Modules\Advanced\Core\MailChimp\ExternalAccount\MailChimp\Client;

class MailChimp implements \Core\Core\ExternalAccount\Clients\IClient
{	

    protected $client = null;
    protected $dc = null;
    protected $apiKey = null;

    protected $paramList = array(
        'apiKey',
        'dc',
    );
    protected $manager = null;

    public function __construct($client, array $params = array(), $manager = null)
    {
        $apiKey = $params['apiKey'];

        if (!empty($apiKey)) {
            list($params['apiKey'], $params['dc']) = explode('-', $apiKey);
        }

        $this->client = $client;
        $this->client->setAccessToken($params['apiKey']);
        $this->setParams($params);
        $this->manager = $manager;
    }

    public function getParam($name)
    {
        if (in_array($name, $this->paramList)) {
            return $this->$name;
        }
    }

    public function setParam($name, $value)
    {
        if (in_array($name, $this->paramList)) {
            $methodName = 'set' . ucfirst($name);
            if (method_exists($this->client, $methodName)) {
                $this->client->$methodName($value);
            }
            $this->$name = $value;
        }
    }

    public function setParams(array $params)
    {
        foreach ($this->paramList as $name) {
            if (!empty($params[$name])) {
                $this->setParam($name, $params[$name]);
            }
        }
    }

    public function baseRequest($url, $params = null, $httpMethod = Client::HTTP_METHOD_GET, $contentType = null, $authType = Client::TOKEN_TYPE_URI)
    {
        $httpHeaders = array();
        if (!empty($contentType)) {
            $httpHeaders['Content-Type'] = $contentType;
            if ($params) {
                switch ($contentType) {
                    case Client::CONTENT_TYPE_MULTIPART_FORM_DATA:
                        $httpHeaders['Content-Length'] = strlen($params);
                        break;
                    case Client::CONTENT_TYPE_APPLICATION_JSON:
                        $httpHeaders['Content-Length'] = strlen($params);
                        break;
                }
            }
        }

        if ($authType == 'basic') {
            $this->client->setAuthType(Client::AUTH_TYPE_URI);
            $this->client->setTokenType(Client::TOKEN_TYPE_BASIC);
        } else {
            $this->client->setAuthType(Client::AUTH_TYPE_AUTHORIZATION_BASIC);
            $this->client->setTokenType(Client::TOKEN_TYPE_URI);
        }
        $r = $this->client->request($url, $params, $httpMethod, $httpHeaders);
        $code = null;

        if (!empty($r['code'])) {
            $code = $r['code'];
        }
        if ($code >= 200 && $code < 300) {
            return $r['result'];
        }
        $messages = [];
        if (isset($r['result']['errors'])) {

            foreach ($r['result']['errors'] as $errorInfo) {
                $messages[] = $errorInfo['message'];
            }
        }

        $error = isset($r['result']['detail']) ? $r['result']['detail'] : '';
        $messagesText = (count($messages)) ? " (" . implode('; ', $messages) .")" : '';
        $GLOBALS['log']->debug('MailChimp Failed Request Params: ' . print_r($params, true));

        throw new Error("MailChimp: Error after requesting {$httpMethod} {$url}. " . $error . $messagesText, $code);
    }

    private function buildUrl($url)
    {
        if ($this->dc == '') {
            throw new Error("MailChimp: Bad APIkey");
        }
        return "https://" . $this->dc . ".api.mailchimp.com/3.0/" . trim($url, '\/');
    }

    public function request($url, $params = null, $method = Client::HTTP_METHOD_GET)
    {
        $contentType = null;
        $writableMethodList = [
            Client::HTTP_METHOD_POST,
            Client::HTTP_METHOD_PUT,
            Client::HTTP_METHOD_PATCH];

        if (in_array($method, $writableMethodList)) {
            $contentType = Client::CONTENT_TYPE_APPLICATION_JSON;
            $params = json_encode($params);
        }
        if (substr($url, 0, 4) != 'http') {
            $url = $this->buildUrl($url);
        }
        $result = $this->baseRequest($url, $params, $method, $contentType, 'basic');
        return $result;
    }

    public function ping()
    {
        $url = 'lists';

        try {
            $this->request($url);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createCampaign($type, $listId, $title, $subject, $fromEmail, $fromName, $toName)
    {
        $url = 'campaigns';

        $params = new \StdClass();
        $params->type = $type;
        $params->recipients = new \StdClass();
        $params->recipients->list_id = $listId;
        $settings = new \StdClass();
        $settings->subject_line = $subject;
        $settings->from_name = $fromName;
        $settings->reply_to = $fromEmail;
        $settings->title = $title;
        $settings->to_name = $toName;
        $params->settings = $settings;

        $result = $this->request($url, $params, 'POST');
        return $result;
    }

    public function createList($name, \StdClass $contact, \StdClass $campaignDefaults, $reminder)
    {
        $params = new \StdClass();
        $params->name = $name;
        $params->contact = $contact;
        $params->campaign_defaults = $campaignDefaults;
        $params->permission_reminder = $reminder;
        $params->email_type_option = true;

        $url = "lists";

        return $this->request($url, $params, 'POST');
    }


    public function getListGroupCategories($listId)
    {
        $url = "/lists/" . $listId . "/interest-categories";
        return $this->request($url);
    }

    public function getParamsForSentToByEmail($campaignId, $email, $operationId)
    {
        $url = '/reports/' . $campaignId . '/sent-to/' . md5(strtolower($email));
        return ['path' => $url, 'method' => 'GET', 'operation_id' => $operationId];
    }

    public function getParamsForEmailActivity($campaignId, $email, $operationId)
    {
        $url = '/reports/' . $campaignId . '/email-activity/' . md5(strtolower($email));
        return ['path' => $url, 'method' => 'GET', 'operation_id' => $operationId];
    }

    public function getParamsForSubscription($listId, $params, $operationId)
    {
        $url = 'lists/' . $listId . '/members/' . md5(strtolower($params['email']));

        $defaultParams = array(
            'status' => 'subscribed',
            'email_address' => $params['email'],
            'status_if_new' => 'subscribed',
        );
        $params = array_merge($defaultParams, $params);

        return ['path' => $url,
                'method' => 'PUT',
                'operation_id' => $operationId,
                'body' => json_encode($params)];
    }

    public function getParamsForUpdateMember($listId, $params, $operationId)
    {
        $email = (isset($params['old_email'])) ? $params['old_email'] : $params['email'];
        $url = 'lists/' . $listId . '/members/' . md5(strtolower($email));

        $defaultParams = array(
            'email_address' => $params['email'],
        );
        $params = array_merge($defaultParams, $params);

        return ['path' => $url,
                'method' => 'PATCH',
                'operation_id' => $operationId,
                'body' => json_encode($params)];
    }

    public function getParamsForUnsubscribe($listId, $params, $operationId)
    {
        $url = 'lists/' . $listId . '/members/' . md5(strtolower($params['email']));

        $defaultParams = array(
            'email_address' => $params['email'],
        );

        if (!isset($params['interests'])) {
            $defaultParams['status'] = 'unsubscribed';
        }
        $params = array_merge($defaultParams, $params);

        return ['path' => $url,
                'method' => 'PATCH',
                'operation_id' => $operationId,
                'body' => json_encode($params)];
    }

    public function getParamsForCleanSubscriber($listId, $params, $operationId)
    {
        $url = 'lists/' . $listId . '/members/' . md5(strtolower($params['email']));

        $defaultParams = array(
            'email_address' => $params['email'],
        );

        if (!isset($params['interests'])) {
            $defaultParams['status'] = 'cleaned';
        }
        $params = array_merge($defaultParams, $params);

        return ['path' => $url,
                'method' => 'PATCH',
                'operation_id' => $operationId,
                'body' => json_encode($params)];
    }

    public function batches(array $operations)
    {
        $url = "/batches";
        $params['operations'] = $operations;
        return $this->request($url, $params, 'POST');
    }

    public function getListGroups($listId, $categoryId)
    {
        $url = "lists/" . $listId . "/interest-categories/" . $categoryId . "/interests";
        return $this->request($url);
    }

    public function getLists($params)
    {
        $url = 'lists';
        $requestParams = array();
        $requestParams['offset'] = (isset($params['offset'])) ? $params['offset'] : 0;
        return $this->request($url, $requestParams);
    }

    public function getCampaigns($params)
    {
        $url = 'campaigns';
        $requestParams = array();
        $requestParams['offset'] = (isset($params['offset'])) ? $params['offset'] : 0;
        $requestParams['type'] = "regular,plaintext,auto,absplit";
        return $this->request($url, $requestParams);
    }

    public function searchCampaigns($params)
    {
        $url = 'search-campaigns';
        $requestParams = array();
        if (isset($params['filter']) && $params['filter']) {
            $requestParams['query'] = "name:" . $params['filter'];
        }

        $requestParams['offset'] = (isset($params['offset'])) ? (int) $params['offset'] : 0;
        return $this->request($url, $requestParams);
    }

    public function getListMembers($listId, $params)
    {
        $url = 'lists/' . $listId . '/members';
        $requestParams = [];
        if (isset($params['subscribedBefore']) && $params['subscribedBefore']) {
            $requestParams['before_timestamp_opt'] = $params['subscribedBefore'];
        }
        $requestParams['offset'] = (isset($params['offset'])) ? (int) $params['offset'] : 0;
        return $this->request($url, $requestParams);
    }

    public function getCampaign($campsignId)
    {
        $url = 'campaigns/' . $campsignId;
        return $this->request($url);
    }

    public function getVarList($listId)
    {
        $url = 'lists/' . $listId . '/merge-fields';
        $requestParams = ['count' => 80];
        return $this->request($url, $requestParams);
    }

    public function addVarToList($listId, $params)
    {
        $url = 'lists/' . $listId . '/merge-fields';
        return $this->request($url, $params, 'POST');
    }

    public function subscribe($listId, array $params)
    {
        $url = 'lists/' . $listId . '/members/' . md5(strtolower($params['email']));

        $defaultParams = array(
            'email_address' => $params['email'],
            'status_if_new' => 'subscribed',
        );
        $params = array_merge($defaultParams, $params);

        return $this->request($url, $params, 'PUT');
    }

    public function unsubscribe($listId, $email)
    {
        $url = 'lists/' . $listId . '/members/' . md5(strtolower($email));
        return $this->request($url, null, 'DELETE');
    }

    public function updateMember($listId, array $params)
    {
        $email = (isset($params['old_email'])) ? $params['old_email'] : $params['email'];
        $url = 'lists/' . $listId . '/members/' . md5(strtolower($email));

        $defaultParams = array(
            'email_address' => $params['email'],
        );
        $params = array_merge($defaultParams, $params);
        return $this->request($url, $params, 'PATCH');
    }

    public function getCampaignActivity($campaignId, $offset = 0)
    {
        $url = "reports/" . $campaignId . "/email-activity";

        $params = ['offset' => $offset];

        return $this->request($url, $params);
    }

    public function getSentTo($campaignId, $offset = 0)
    {
        $url = 'reports/' . $campaignId . '/sent-to';
        $params = ['offset' => $offset];
        return $this->request($url, $params);
    }

    public function getCampaignContent($campaignId)
    {
        $url = 'campaigns/' . $campaignId . '/content';
        return $this->request($url);
    }

    public function getCampaignOptedOutReport($campaignId, $offset = 0)
    {
        $url = "reports/" . $campaignId. "/unsubscribed";
        $params = ['offset' => $offset];
        return $this->request($url, $params);
    }

    public function getUnsubscribedMembersFromList($listId, $offset = 0)
    {
        $url = "lists/" . $listId. "/members";
        $params = array(
            'offset' => $offset,
            'status' => 'unsubscribed'
        );
        return $this->request($url, $params);
    }

    public function getBatch($batchId)
    {
        $url = 'batches/' . $batchId;
        return $this->request($url);
    }

}
