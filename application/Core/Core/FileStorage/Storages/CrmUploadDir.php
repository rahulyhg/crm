<?php


namespace Core\Core\FileStorage\Storages;

use \Core\Entities\Attachment;

use \Core\Core\Exceptions\Error;

class CoreUploadDir extends Base
{
    protected $dependencyList = ['fileManager'];

    protected function getFileManager()
    {
        return $this->getInjection('fileManager');
    }

    public function unlink(Attachment $attachment)
    {
        return $this->getFileManager()->unlink($this->getFilePath($attachment));
    }

    public function isFile(Attachment $attachment)
    {
        return $this->getFileManager()->isFile($this->getFilePath($attachment));
    }

    public function getContents(Attachment $attachment)
    {
        return $this->getFileManager()->getContents($this->getFilePath($attachment));
    }

    public function putContents(Attachment $attachment, $contents)
    {
        return $this->getFileManager()->putContents($this->getFilePath($attachment), $contents);
    }

    public function getLocalFilePath(Attachment $attachment)
    {
        return $this->getFilePath($attachment);
    }

    protected function getFilePath(Attachment $attachment)
    {
        $sourceId = $attachment->getSourceId();
        return 'data/upload/' . $sourceId;
    }

    public function getDownloadUrl(Attachment $attachment)
    {
        throw new Error();
    }

    public function hasDownloadUrl(Attachment $attachment)
    {
        return false;
    }
}
