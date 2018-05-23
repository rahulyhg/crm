<?php


namespace Core\Core\Upgrades\Actions\Extension;

class Upload extends \Core\Core\Upgrades\Actions\Base\Upload
{
    protected function checkDependencies($dependencyList)
    {
        return $this->getHelper()->checkDependencies($dependencyList);
    }
}