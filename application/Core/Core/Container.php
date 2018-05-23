<?php


namespace Core\Core;

class Container
{

    private $data = array();


    /**
     * Constructor
     */
    public function __construct()
    {
    }

    public function get($name)
    {
        if (empty($this->data[$name])) {
            $this->load($name);
        }
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }

    protected function set($name, $obj)
    {
        $this->data[$name] = $obj;
    }

    private function load($name)
    {
        $loadMethod = 'load' . ucfirst($name);
        if (method_exists($this, $loadMethod)) {
            $obj = $this->$loadMethod();
            $this->data[$name] = $obj;
        } else {

            try {
                $className = $this->get('metadata')->get('app.loaders.' . ucfirst($name));
            } catch (\Exception $e) {}

            if (!isset($className) || !class_exists($className)) {
                $className = '\Core\Custom\Core\Loaders\\'.ucfirst($name);
                if (!class_exists($className)) {
                    $className = '\Core\Core\Loaders\\'.ucfirst($name);
                }
            }

            if (class_exists($className)) {
                 $loadClass = new $className($this);
                 $this->data[$name] = $loadClass->load();
            }
        }

        return null;
    }

    protected function getServiceClassName($name, $default)
    {
        $metadata = $this->get('metadata');
        $className = $metadata->get('app.serviceContainer.classNames.' . $name, $default);
        return $className;
    }

    protected function loadContainer()
    {
        return $this;
    }

    protected function loadSlim()
    {
        return new \Core\Core\Utils\Api\Slim();
    }

    protected function loadFileStorageManager()
    {
        return new \Core\Core\FileStorage\Manager(
            $this->get('metadata')->get(['app', 'fileStorage', 'implementationClassNameMap']),
            $this
        );
    }

    protected function loadFileManager()
    {
        return new \Core\Core\Utils\File\Manager(
            $this->get('config')
        );
    }

    protected function loadControllerManager()
    {
        return new \Core\Core\ControllerManager(
            $this
        );
    }

    protected function loadPreferences()
    {
        return $this->get('entityManager')->getEntity('Preferences', $this->get('user')->id);
    }

    protected function loadConfig()
    {
        return new \Core\Core\Utils\Config(
            new \Core\Core\Utils\File\Manager()
        );
    }

    protected function loadHookManager()
    {
        return new \Core\Core\HookManager(
            $this
        );
    }

    protected function loadOutput()
    {
        return new \Core\Core\Utils\Api\Output(
            $this->get('slim')
        );
    }

    protected function loadMailSender()
    {
        $className = $this->getServiceClassName('mailSernder', '\\Core\\Core\\Mail\\Sender');
        return new $className(
            $this->get('config'),
            $this->get('entityManager')
        );
    }

    protected function loadDateTime()
    {
        return new \Core\Core\Utils\DateTime(
            $this->get('config')->get('dateFormat'),
            $this->get('config')->get('timeFormat'),
            $this->get('config')->get('timeZone')
        );
    }

    protected function loadNumber()
    {
        return new \Core\Core\Utils\NumberUtil(
            $this->get('config')->get('decimalMark'),
            $this->get('config')->get('thousandSeparator')
        );
    }

    protected function loadServiceFactory()
    {
        return new \Core\Core\ServiceFactory(
            $this
        );
    }

    protected function loadSelectManagerFactory()
    {
        return new \Core\Core\SelectManagerFactory(
            $this->get('entityManager'),
            $this->get('user'),
            $this->get('acl'),
            $this->get('aclManager'),
            $this->get('metadata'),
            $this->get('config')
        );
    }

    protected function loadMetadata()
    {
        return new \Core\Core\Utils\Metadata(
            $this->get('fileManager'),
            $this->get('config')->get('useCache')
        );
    }

    protected function loadLayout()
    {
        return new \Core\Core\Utils\Layout(
            $this->get('fileManager'),
            $this->get('metadata'),
            $this->get('user')
        );
    }

    protected function loadAclManager()
    {
        $className = $this->getServiceClassName('acl', '\\Core\\Core\\AclManager');
        return new $className(
            $this->get('container')
        );
    }

    protected function loadAcl()
    {
        $className = $this->getServiceClassName('acl', '\\Core\\Core\\Acl');
        return new $className(
            $this->get('aclManager'),
            $this->get('user')
        );
    }

    protected function loadSchema()
    {
        return new \Core\Core\Utils\Database\Schema\Schema(
            $this->get('config'),
            $this->get('metadata'),
            $this->get('fileManager'),
            $this->get('entityManager'),
            $this->get('classParser'),
            $this->get('ormMetadata')
        );
    }

    protected function loadOrmMetadata()
    {
        return new \Core\Core\Utils\Metadata\OrmMetadata(
            $this->get('metadata'),
            $this->get('fileManager'),
            $this->get('config')->get('useCache')
        );
    }

    protected function loadClassParser()
    {
        return new \Core\Core\Utils\File\ClassParser(
            $this->get('fileManager'),
            $this->get('config'),
            $this->get('metadata')
        );
    }

    protected function loadLanguage()
    {
        return new \Core\Core\Utils\Language(
            \Core\Core\Utils\Language::detectLanguage($this->get('config'), $this->get('preferences')),
            $this->get('fileManager'),
            $this->get('metadata'),
            $this->get('config')->get('useCache')
        );
    }

    protected function loadDefaultLanguage()
    {
        return new \Core\Core\Utils\Language(
            null,
            $this->get('fileManager'),
            $this->get('metadata'),
            $this->get('useCache')
        );
    }

    protected function loadCrypt()
    {
        return new \Core\Core\Utils\Crypt(
            $this->get('config')
        );
    }

    protected function loadScheduledJob()
    {
        return new \Core\Core\Utils\ScheduledJob(
            $this
        );
    }

    protected function loadDataManager()
    {
        return new \Core\Core\DataManager(
            $this
        );
    }

    protected function loadFieldManager()
    {
        return new \Core\Core\Utils\FieldManager(
            $this->get('metadata'),
            $this->get('language'),
            $this
        );
    }

    protected function loadFieldManagerUtil()
    {
        return new \Core\Core\Utils\FieldManagerUtil(
            $this->get('metadata')
        );
    }

    protected function loadThemeManager()
    {
        return new \Core\Core\Utils\ThemeManager(
            $this->get('config'),
            $this->get('metadata')
        );
    }

    protected function loadClientManager()
    {
        return new \Core\Core\Utils\ClientManager(
            $this->get('config'),
            $this->get('themeManager')
        );
    }

    protected function loadInjectableFactory()
    {
        return new \Core\Core\InjectableFactory(
            $this
        );
    }

    public function setUser(\Core\Entities\User $user)
    {
        $this->set('user', $user);
    }
}

