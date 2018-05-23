<?php
 

namespace Core\Core;
use \Core\Core\Exceptions\NotFound,
    \Core\Core\Utils\Util;


class EntryPointManager
{
    private $container;

    private $fileManager;

    protected $data = null;

    protected $cacheFile = 'data/cache/application/entryPoints.php';

    protected $allowedMethods = array(
        'run',
    );

    /**
     * @var array - path to entryPoint files
     */
    private $paths = array(
        'corePath' => 'application/Core/EntryPoints',
        'modulePath' => 'application/Core/Modules/{*}/EntryPoints',
        'customPath' => 'custom/Core/Custom/EntryPoints',
    );


    public function __construct(\Core\Core\Container $container)
    {
        $this->container = $container;
        $this->fileManager = $container->get('fileManager');
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    public function checkAuthRequired($name)
    {
        $className = $this->getClassName($name);
        if (!$className) {
            throw new NotFound();
        }
        return $className::$authRequired;
    }

    public function checkNotStrictAuth($name)
    {
        $className = $this->getClassName($name);
        if (!$className) {
            throw new NotFound();
        }
        return $className::$notStrictAuth;
    }

    public function run($name, $data = array())
    {
        $className = $this->getClassName($name);
        if (!$className) {
            throw new NotFound();
        }
        $entryPoint = new $className($this->container);

        $entryPoint->run($data);
    }

    protected function getClassName($name)
    {
        $name = Util::normilizeClassName($name);

        if (!isset($this->data)) {
            $this->init();
        }

        $name = ucfirst($name);
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return false;
    }


    protected function init()
    {
        $classParser = $this->getContainer()->get('classParser');
        $classParser->setAllowedMethods($this->allowedMethods);
        $this->data = $classParser->getData($this->paths, $this->cacheFile);
    }

}

