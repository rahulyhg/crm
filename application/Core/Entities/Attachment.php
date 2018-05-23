<?php


namespace Core\Entities;

class Attachment extends \Core\Core\ORM\Entity
{
    public function getSourceId()
    {
        $sourceId = $this->get('sourceId');
        if (!$sourceId) {
            $sourceId = $this->id;
        }
        return $sourceId;
    }

}
