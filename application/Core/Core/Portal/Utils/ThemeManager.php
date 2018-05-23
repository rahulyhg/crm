<?php


namespace Core\Core\Portal\Utils;

use \Core\Entities\Portal;

use \Core\Core\Utils\Config;
use \Core\Core\Utils\Metadata;

class ThemeManager extends \Core\Core\Utils\ThemeManager
{
    public function __construct(Config $config, Metadata $metadata, Portal $portal)
    {
        $this->config = $config;
        $this->metadata = $metadata;
        $this->portal = $portal;
    }

    public function getName()
    {
        $theme = $this->portal->get('theme');
        if (!$theme) {
            $theme = $this->config->get('theme', $this->defaultName);
        }
        return $theme;
    }
}


