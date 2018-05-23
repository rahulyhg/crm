<?php


namespace Core\Core\Portal;

class Container extends \Core\Core\Container
{
    protected function getServiceClassName($name, $default)
    {
        $metadata = $this->get('metadata');
        $className = $metadata->get('app.serviceContainerPortal.classNames.' . $name, $default);
        return $className;
    }

    protected function getServiceMainClassName($name, $default)
    {
        $metadata = $this->get('metadata');
        $className = $metadata->get('app.serviceContainer.classNames.' . $name, $default);
        return $className;
    }

    protected function loadAclManager()
    {
        $className = $this->getServiceClassName('aclManager', '\\Core\\Core\\Portal\\AclManager');
        $mainClassName = $this->getServiceMainClassName('aclManager', '\\Core\\Core\\AclManager');

        $obj = new $className(
            $this->get('container')
        );
        $objMain = new $mainClassName(
            $this->get('container')
        );
        $obj->setMainManager($objMain);

        return $obj;
    }

    protected function loadAcl()
    {
        $className = $this->getServiceClassName('acl', '\\Core\\Core\\Portal\\Acl');
        return new $className(
            $this->get('aclManager'),
            $this->get('user')
        );
    }

    protected function loadThemeManager()
    {
        return new \Core\Core\Portal\Utils\ThemeManager(
            $this->get('config'),
            $this->get('metadata'),
            $this->get('portal')
        );
    }

    protected function loadLayout()
    {
        return new \Core\Core\Portal\Utils\Layout(
            $this->get('fileManager'),
            $this->get('metadata'),
            $this->get('user')
        );
    }

    protected function loadLanguage()
    {
        $language = new \Core\Core\Portal\Utils\Language(
            \Core\Core\Utils\Language::detectLanguage($this->get('config'), $this->get('preferences')),
            $this->get('fileManager'),
            $this->get('metadata'),
            $this->get('useCache')
        );
        $language->setPortal($this->get('portal'));
        return $language;
    }

    public function setPortal(\Core\Entities\Portal $portal)
    {
        $this->set('portal', $portal);

        $data = array();
        foreach ($this->get('portal')->getSettingsAttributeList() as $attribute) {
            $data[$attribute] = $this->get('portal')->get($attribute);
        }
        if (empty($data['language'])) {
            unset($data['language']);
        }
        if (empty($data['theme'])) {
            unset($data['theme']);
        }
        if (empty($data['timeZone'])) {
            unset($data['timeZone']);
        }
        if (empty($data['dateFormat'])) {
            unset($data['dateFormat']);
        }
        if (empty($data['timeFormat'])) {
            unset($data['timeFormat']);
        }
        if (isset($data['weekStart']) && $data['weekStart'] === -1) {
            unset($data['weekStart']);
        }
        if (array_key_exists('weekStart', $data) && is_null($data['weekStart'])) {
            unset($data['weekStart']);
        }
        if (empty($data['defaultCurrency'])) {
            unset($data['defaultCurrency']);
        }

        foreach ($data as $attribute => $value) {
            $this->get('config')->set($attribute, $value, true);
        }
    }
}

