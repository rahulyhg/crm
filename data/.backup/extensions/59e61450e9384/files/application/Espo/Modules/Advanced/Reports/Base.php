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

namespace Core\Modules\Advanced\Reports;

use \Core\Core\Container;

abstract class Base
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }


    protected function getMetadata()
    {
        return $this->getContainer()->get('metadata');
    }

    protected function getLanguage()
    {
        return $this->getContainer()->get('language');
    }

    protected function getSelectManagerFactory()
    {
        return $this->getContainer()->get('selectManagerFactory');
    }

    abstract public function run($where = null, array $params = null);
}

