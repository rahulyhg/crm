<?php


namespace Core\EntryPoints;

use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;

class Download extends \Core\Core\EntryPoints\Base
{
    public static $authRequired = true;

    protected $fileTypesToShowInline = array(
        'application/pdf',
        'application/vnd.ms-word',
        'application/vnd.ms-excel',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'text/plain',
        'application/msword',
        'application/msexcel'
    );

    public function run()
    {
        if (empty($_GET['id'])) {
            throw new BadRequest();
        }
        $id = $_GET['id'];

        $attachment = $this->getEntityManager()->getEntity('Attachment', $id);

        if (!$attachment) {
            throw new NotFound();
        }

        if (!$this->getAcl()->checkEntity($attachment)) {
            throw new Forbidden();
        }

        $sourceId = $attachment->getSourceId();

        if ($this->getEntityManager()->getRepository('Attachment')->hasDownloadUrl($attachment)) {
            $downloadUrl = $this->getEntityManager()->getRepository('Attachment')->getDownloadUrl($attachment);
            header('Location: ' . $downloadUrl);
            exit;
        }

        $fileName = $this->getEntityManager()->getRepository('Attachment')->getFilePath($attachment);

        if (!file_exists($fileName)) {
            throw new NotFound();
        }

        $outputFileName = $attachment->get('name');
        $outputFileName = str_replace("\"", "\\\"", $outputFileName);

        $type = $attachment->get('type');

        $disposition = 'attachment';
        if (in_array($type, $this->fileTypesToShowInline)) {
            $disposition = 'inline';
        }

        header('Content-Description: File Transfer');
        if ($type) {
            header('Content-Type: ' . $type);
        }
        header("Content-Disposition: " . $disposition . ";filename=\"" . $outputFileName . "\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fileName));

        readfile($fileName);
        exit;
    }
}

