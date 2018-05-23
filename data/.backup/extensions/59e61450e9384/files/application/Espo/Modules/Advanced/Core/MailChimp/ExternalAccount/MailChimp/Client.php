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

namespace Core\Modules\Advanced\Core\MailChimp\ExternalAccount\MailChimp;

class Client extends \Core\Core\ExternalAccount\OAuth2\Client
{
    const TOKEN_TYPE_BASIC = 'Basic';

    protected $accessTokenParamName = "apikey";
    protected $apikey = null;

    public function request($url, $params = null, $httpMethod = self::HTTP_METHOD_GET, array $httpHeaders = array())
    {
        if ($this->accessToken) {
            switch ($this->tokenType) {
                case self::TOKEN_TYPE_URI:
                    $params[$this->accessTokenParamName] = $this->accessToken;
                    break;
                case self::TOKEN_TYPE_BEARER:
                    $httpHeaders['Authorization'] = 'Bearer ' . $this->accessToken;
                    break;
                case self::TOKEN_TYPE_OAUTH:
                    $httpHeaders['Authorization'] = 'OAuth ' . $this->accessToken;
                    break;
                case self::TOKEN_TYPE_BASIC:
                $httpHeaders['Authorization'] = 'Basic ' . base64_encode($this->accessTokenParamName . ':' . $this->accessToken);
                break;
                default:
                    throw new \Exception('Unknown access token type.');

            }
        }
        return $this->execute($url, $params, $httpMethod, $httpHeaders);
    }

    private function execute($url, $params = null, $httpMethod, array $httpHeaders = array())
    {
        $curlOptions = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST => $httpMethod
        );

        switch ($httpMethod) {
            case self::HTTP_METHOD_POST:
                $curlOptions[CURLOPT_POST] = true;
            case self::HTTP_METHOD_PUT:
            case self::HTTP_METHOD_PATCH:
                if (is_array($params)) {
                    $postFields = http_build_query($params, null, '&');
                } else {
                    $postFields = $params;
                }
                $curlOptions[CURLOPT_POSTFIELDS] = $postFields;
                break;
            case self::HTTP_METHOD_HEAD:
                $curlOptions[CURLOPT_NOBODY] = true;
            case self::HTTP_METHOD_DELETE:
            case self::HTTP_METHOD_GET:
                if (strpos($url, '?') === false) {
                    $url .= '?';
                }
                if (is_array($params)) {
                    $url .= http_build_query($params, null, '&');
                }
                break;
            default:
                break;
        }
        $curlOptions[CURLOPT_URL] = $url;

        $curlOptHttpHeader = array();
        foreach ($httpHeaders as $key => $value) {
             $curlOptHttpHeader[] = "{$key}: {$value}";
        }
        $curlOptions[CURLOPT_HTTPHEADER] = $curlOptHttpHeader;

        $ch = curl_init();
        curl_setopt_array($ch, $curlOptions);

        curl_setopt($ch, CURLOPT_HEADER, 1);

        if (!empty($this->certificateFile)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, $this->certificateFile);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        if (!empty($this->curlOptions)) {
            curl_setopt_array($ch, $this->curlOptions);
        }

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        $responceHeader = substr($response, 0, $headerSize);
        $responceBody = substr($response, $headerSize);
        $resultArray = null;

        if ($curlError = curl_error($ch)) {
            throw new \Exception($curlError);
        } else {
            $resultArray = json_decode($responceBody, true);
        }
        curl_close($ch);

        return array(
            'result' => (null !== $resultArray) ? $resultArray: $responceBody,
            'code' => intval($httpCode),
            'contentType' => $contentType,
            'header' => $responceHeader,
        );
    }

}
