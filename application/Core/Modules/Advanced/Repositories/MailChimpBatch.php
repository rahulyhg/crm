<?php
/*********************************************************************************
 * The contents of this file are subject to the CoreCRM Advanced
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

namespace Core\Modules\Advanced\Repositories;

use Core\ORM\Entity;

class MailChimpBatch extends \Core\Core\ORM\Repositories\RDB
{
    protected function init()
    {
        parent::init();
        $this->addDependency('fileManager');
    }

    protected function getFileManager()
    {
        return $this->injections['fileManager'];
    }

    public function getFileName(Entity $entity)
    {
        return 'mc-' . $entity->get('name') . '-response';
    }

    public function getDataPath(Entity $entity)
    {
        $workDir = 'data/upload/';
        return $workDir . $this->getFileName($entity);
    }

    public function saveContentFromUrl(Entity $entity, $contentUrl)
    {
        $dataPath = $this->getDataPath($entity);

        if (!is_dir($dataPath)) {
            try {
                $tarGzPath = $dataPath . '.tar.gz';
                if (!file_exists($tarGzPath)) {
                    $content = file_get_contents($contentUrl);
                    $this->getFileManager()->putContents($tarGzPath, $content);
                }

                if (!file_exists($dataPath . '.zip')) {
                    if (!class_exists('\PharData')) {
                        throw new Error("Class PharData does not installed. Cannot unzip the file.");
                    }
                    $p = new \PharData($tarGzPath);
                    $p->convertToData(\Phar::ZIP);
                }

                $zipUtil = new \Core\Core\Utils\File\ZipArchive($this->getFileManager());
                $zipUtil->unzip($dataPath . '.zip', $dataPath);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('MailChimp Batch Result Unzip Error:' . $e->getMessage());
                return false;
            }
        }
        return true;
    }

    protected function afterRemove(Entity $entity, array $options = array())
    {
        parent::afterRemove($entity, $options);

        $dataPath = $this->getDataPath($entity);

        if (file_exists($dataPath . '.tar.gz')) {
            $this->getFileManager()->unlink($dataPath . '.tar.gz');
        }

        if (file_exists($dataPath . '.zip')) {
            $this->getFileManager()->unlink($dataPath . '.zip');
        }

        if (is_dir($dataPath)) {
            $this->getFileManager()->removeInDir($dataPath, true);
        }

    }

}
