<?php


namespace Core\Core\Utils;

class TemplateFileManager
{
    protected $config;

    protected $metadata;

    public function __construct(Config $config, Metadata $metadata)
    {
        $this->config = $config;
        $this->metadata = $metadata;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    public function getTemplate($type, $name, $entityType = null, $defaultModuleName = null)
    {
        $fileName = $this->getTemplateFileName($type, $name, $entityType, $defaultModuleName);

        return file_get_contents($fileName);
    }

    protected function getTemplateFileName($type, $name, $entityType = null, $defaultModuleName = null)
    {
        $language = $this->getConfig()->get('language');

        if ($entityType) {
            $moduleName = $this->getMetadata()->getScopeModuleName($entityType);

            $fileName = "custom/Core/Custom/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
            if (file_exists($fileName)) return $fileName;

            if ($moduleName) {
                $fileName = "application/Core/Modules/{$moduleName}/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
                if (file_exists($fileName)) return $fileName;
            }

            $fileName = "application/Core/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
            if (file_exists($fileName)) return $fileName;
        }

        $fileName = "custom/Core/Custom/Resources/templates/{$type}/{$language}/{$name}.tpl";
        if (file_exists($fileName)) return $fileName;

        if ($defaultModuleName) {
            $fileName = "application/Core/Modules/{$defaultModuleName}/Resources/templates/{$type}/{$language}/{$name}.tpl";
        } else {
            $fileName = "application/Core/Resources/templates/{$type}/{$language}/{$name}.tpl";
        }
        if (file_exists($fileName)) return $fileName;

        $language = 'en_US';

        if ($entityType) {
            $fileName = "custom/Core/Custom/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
            if (file_exists($fileName)) return $fileName;

            if ($moduleName) {
                $fileName = "application/Core/Modules/{$moduleName}/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
                if (file_exists($fileName)) return $fileName;
            }

            $fileName = "application/Core/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
            if (file_exists($fileName)) return $fileName;
        }

        $fileName = "custom/Core/Custom/Resources/templates/{$type}/{$language}/{$name}.tpl";
        if (file_exists($fileName)) return $fileName;

        if ($defaultModuleName) {
            $fileName = "application/Core/Modules/{$defaultModuleName}/Resources/templates/{$type}/{$language}/{$name}.tpl";
        } else {
            $fileName = "application/Core/Resources/templates/{$type}/{$language}/{$name}.tpl";
        }

        return $fileName;
    }
}

