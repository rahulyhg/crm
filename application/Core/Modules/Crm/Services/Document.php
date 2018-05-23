<?php


namespace Core\Modules\Crm\Services;

use \Core\ORM\Entity;
use \Core\Core\Exceptions\NotFound;

class Document extends \Core\Services\Record
{
    public function getAttachmentList($id)
    {
        $entity = $this->getEntity($id);

        if (!$entity) {
            throw new NotFound();
        }

        $fileId = $entity->get('fileId');
        if (!$fileId) {
            throw new NotFound();
        }

        $file = $this->getEntityManager()->getEntity('Attachment', $fileId);
        if (!$file) {
            throw new NotFound();
        }

        $attachment = $this->getEntityManager()->getRepository('Attachment')->getCopiedAttachment($file, 'Attachment');

        $attachmentList = $this->getEntityManager()->createCollection('Attachment');
        $attachmentList[] = $attachment;

        return $attachmentList;
    }
}

