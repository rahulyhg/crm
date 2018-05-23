<?php


namespace Core\Core\Loaders;

class TemplateFileManager extends Base
{
    public function load()
    {
        $templateFileManager = new \Core\Core\Utils\TemplateFileManager(
            $this->getContainer()->get('config'),
            $this->getContainer()->get('metadata')
        );

        return $templateFileManager;
    }
}

