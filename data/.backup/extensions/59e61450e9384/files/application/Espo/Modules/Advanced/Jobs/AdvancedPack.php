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

namespace Core\Modules\Advanced\Jobs;

class AdvancedPack extends \Core\Core\Jobs\Base
{
    public function run()
    {
        $helper = new \Core\Modules\Advanced\Core\Helper($this->getContainer());
        $info = $helper->getInfo();

        if (!empty($info)) {
            $data = array(
                'id' => @$info['lid'],
                'name' => @$info['name'],
                'site' => $this->getConfig()->get('siteUrl'),
                'version' => @$info['version'],
                'installedAt' => @$info['installedAt'],
                'updatedAt' => @$info['created_at'],
                'applicationName' => $this->getConfig()->get('applicationName'),
                'espoVersion' => $this->getConfig()->get('version'),
            );

            $result = $this->validate($data);
        }
    }

    protected function validate(array $data)
    {
        if (function_exists('curl_version')) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, base64_decode('aHR0cHM6Ly9zLmVzcG9jcm0uY29tLw=='));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('data' => json_encode($data))));

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                return $result;
            }
        }
    }
}