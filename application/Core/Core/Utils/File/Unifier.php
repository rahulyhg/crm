<?php


namespace Core\Core\Utils\File;

use Core\Core\Utils;

class Unifier
{
    private $fileManager;

    private $metadata;

    protected $useObjects;


    protected $unsetFileName = 'unset.json';

    protected $pathToDefaults = 'application/Core/Core/defaults';

    public function __construct(\Core\Core\Utils\File\Manager $fileManager, \Core\Core\Utils\Metadata $metadata = null, $useObjects = false)
    {
        $this->fileManager = $fileManager;
        $this->metadata = $metadata;
        $this->useObjects = $useObjects;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Unite file content to the file
     *
     * @param  string  $name
     * @param  array  $paths
     * @param  boolean $recursively Note: only for first level of sub directory, other levels of sub directories will be ignored
     *
     * @return array
     */
    public function unify($name, $paths, $recursively = false)
    {
        $content = $this->unifySingle($paths['corePath'], $name, $recursively);

        if (!empty($paths['modulePath'])) {
            $customDir = strstr($paths['modulePath'], '{*}', true);

            $moduleList = isset($this->metadata) ? $this->getMetadata()->getModuleList() : $this->getFileManager()->getFileList($customDir, false, '', false);

            foreach ($moduleList as $moduleName) {
                $curPath = str_replace('{*}', $moduleName, $paths['modulePath']);
                $content = Utils\Util::merge($content, $this->unifySingle($curPath, $name, $recursively, $moduleName));
            }
        }

        if (!empty($paths['customPath'])) {
            $content = Utils\Util::merge($content, $this->unifySingle($paths['customPath'], $name, $recursively));
        }

        return $content;
    }

    /**
     * Unite file content to the file for one directory [NOW ONLY FOR METADATA, NEED TO CHECK FOR LAYOUTS AND OTHERS]
     *
     * @param string $dirPath
     * @param string $type - name of type array("metadata", "layouts"), ex. $this->name
     * @param bool $recursively - Note: only for first level of sub directory, other levels of sub directories will be ignored
     * @param string $moduleName - name of module if exists
     *
     * @return string - content of the files
     */
    protected function unifySingle($dirPath, $type, $recursively = false, $moduleName = '')
    {
        if (empty($dirPath) || !file_exists($dirPath)) {
            return false;
        }

        $fileList = $this->getFileManager()->getFileList($dirPath, $recursively, '\.json$');

        $dirName = $this->getFileManager()->getDirName($dirPath, false);
        $defaultValues = $this->loadDefaultValues($dirName, $type);

        $content = array();
        $unsets = array();

        foreach ($fileList as $dirName => $fileName) {
            if (is_array($fileName)) {
                $content[$dirName]= $this->unifySingle(Utils\Util::concatPath($dirPath, $dirName), $type, false, $moduleName); //only first level of a sub directory

            } else {
                if ($fileName === $this->unsetFileName) {
                    $fileContent = $this->getFileManager()->getContents(array($dirPath, $fileName));
                    if ($this->useObjects) {
                        $unsets = Utils\Json::decode($fileContent);
                    } else {
                        $unsets = Utils\Json::getArrayData($fileContent);
                    }
                    continue;
                }

                $mergedValues = $this->unifyGetContents(array($dirPath, $fileName), $defaultValues);

                if (!empty($mergedValues)) {
                    $name = $this->getFileManager()->getFileName($fileName, '.json');
                    $content[$name] = $mergedValues;
                }
            }
        }

        if ($this->useObjects) {
            $content = Utils\DataUtil::unsetByKey($content, $unsets);
        } else {
            $content = Utils\Util::unsetInArray($content, $unsets);
        }

        return $content;
    }

    /**
     * Helpful method for get content from files for unite Files
     *
     * @param string | array $paths
     * @param string | array() $defaults - It can be a string like ["metadata","layouts"] OR an array with default values
     *
     * @return array
     */
    protected function unifyGetContents($paths, $defaults)
    {
        $fileContent = $this->getFileManager()->getContents($paths);

        if ($this->useObjects) {
            $decoded = Utils\Json::decode($fileContent);
        } else {
            $decoded = Utils\Json::getArrayData($fileContent, null);
        }

        if (!isset($decoded)) {
            $GLOBALS['log']->emergency('Syntax error in '.Utils\Util::concatPath($paths));
            if ($this->useObjects) {
                return (object) [];
            } else {
                return array();
            }
        }

        return $decoded;
    }

    /**
     * Load default values for selected type [metadata, layouts]
     *
     * @param string $name
     * @param string $type - [metadata, layouts]
     *
     * @return array
     */
    protected function loadDefaultValues($name, $type = 'metadata')
    {
        $defaultValue = $this->getFileManager()->getContents(array($this->pathToDefaults, $type, $name.'.json') );
        if ($defaultValue !== false) {
            if ($this->useObjects) {
                return Utils\Json::decode($defaultValue);
            } else {
                return Utils\Json::decode($defaultValue, true);
            }
        }
        if ($this->useObjects) {
            return (object) [];
        } else {
            return array();
        }
    }

}

?>