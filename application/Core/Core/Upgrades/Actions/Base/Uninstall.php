<?php


namespace Core\Core\Upgrades\Actions\Base;
use Core\Core\Exceptions\Error;
use Core\Core\Utils\Util;
use Core\Core\Utils\Json;

class Uninstall extends \Core\Core\Upgrades\Actions\Base
{
    public function run($data)
    {
        $processId = $data['id'];

        $GLOBALS['log']->debug('Uninstallation process ['.$processId.']: start run.');

        if (empty($processId)) {
            throw new Error('Uninstallation package ID was not specified.');
        }

        $this->setProcessId($processId);

        $this->initialize();

        $this->checkIsWritable();

        $this->beforeRunAction();

        /* run before install script */
        if (!isset($data['isNotRunScriptBefore']) || !$data['isNotRunScriptBefore']) {
            $this->runScript('beforeUninstall');
        }

        $backupPath = $this->getPath('backupPath');
        if (file_exists($backupPath)) {

            /* copy core files */
            if (!$this->copyFiles()) {
                $this->throwErrorAndRemovePackage('Cannot copy files.');
            }

            /* remove extension files, saved in fileList */
            if (!$this->deleteFiles(true)) {
                $this->throwErrorAndRemovePackage('Permission denied to delete files.');
            }
        }

        if (!$this->systemRebuild()) {
            $this->throwErrorAndRemovePackage('Error occurred while CRM rebuild.');
        }

        /* run after uninstall script */
        if (!isset($data['isNotRunScriptAfter']) || !$data['isNotRunScriptAfter']) {
            $this->runScript('afterUninstall');
        }

        $this->afterRunAction();

        $this->clearCache();

        /* delete backup files */
        $this->deletePackageFiles();

        $this->finalize();

        $GLOBALS['log']->debug('Uninstallation process ['.$processId.']: end run.');
    }

    protected function restoreFiles()
    {
        $packagePath = $this->getPath('packagePath');

        $manifestPath = Util::concatPath($packagePath, $this->manifestName);
        if (!file_exists($manifestPath)) {
            $this->unzipArchive($packagePath);
        }

        $fileDirs = $this->getFileDirs($packagePath);
        foreach ($fileDirs as $filesPath) {
            if (file_exists($filesPath)) {
                $res = $this->copy($filesPath, '', true);
            }
        }

        $manifestJson = $this->getFileManager()->getContents($manifestPath);
        $manifest = Json::decode($manifestJson, true);
        if (!empty($manifest['delete'])) {
            $res &= $this->getFileManager()->remove($manifest['delete'], null, true);
        }

        $res &= $this->getFileManager()->removeInDir($packagePath, true);

        return $res;
    }

    protected function copyFiles($type = null)
    {
        $backupPath = $this->getPath('backupPath');
        $res = $this->copy(array($backupPath, self::FILES), '', true);

        return $res;
    }

    /**
     * Get backup path
     *
     * @param  string $processId
     * @return string
     */
    protected function getPackagePath($isPackage = false)
    {
        if ($isPackage) {
            return $this->getPath('packagePath', $isPackage);
        }

        return $this->getPath('backupPath');
    }

    protected function deletePackageFiles()
    {
        $backupPath = $this->getPath('backupPath');
        $res = $this->getFileManager()->removeInDir($backupPath, true);

        return $res;
    }

    protected function throwErrorAndRemovePackage($errorMessage = '')
    {
        $this->restoreFiles();
        throw new Error($errorMessage);
    }

    protected function getCopyFileList()
    {
        if (!isset($this->data['fileList'])) {
            $backupPath = $this->getPath('backupPath');
            $filesPath = Util::concatPath($backupPath, self::FILES);

            $this->data['fileList'] = $this->getFileManager()->getFileList($filesPath, true, '', true, true);
        }

        return $this->data['fileList'];
    }

    protected function getRestoreFileList()
    {
        if (!isset($this->data['restoreFileList'])) {
            $packagePath = $this->getPackagePath();
            $filesPath = Util::concatPath($packagePath, self::FILES);

            if (!file_exists($filesPath)) {
                $this->unzipArchive($packagePath);
            }

            $this->data['restoreFileList'] = $this->getFileManager()->getFileList($filesPath, true, '', true, true);
        }

        return $this->data['restoreFileList'];
    }

    protected function getDeleteList($type = 'delete')
    {
        if ($type == 'delete') {
            $packageFileList = $this->getRestoreFileList();
            $backupFileList = $this->getCopyFileList();

            return array_diff($packageFileList, $backupFileList);
        }

        return array();
    }
}
