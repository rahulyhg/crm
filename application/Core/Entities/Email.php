<?php


namespace Core\Entities;

class Email extends \Core\Core\ORM\Entity
{
    protected function _getSubject()
    {
        return $this->get('name');
    }

    protected function _setSubject($value)
    {
        $this->set('name', $value);
    }

    protected function _setIsRead($value)
    {
        $this->setValue('isRead', $value !== false);
        if ($value === true || $value === false) {
            $this->setValue('isUsers', true);
        } else {
            $this->setValue('isUsers', false);
        }
    }

    public function isManuallyArchived()
    {
        return $this->get('status') === 'Archived' && $this->get('createdById') !== 'system';
    }

    public function addAttachment(\Core\Entities\Attachment $attachment)
    {
        if (!empty($this->id)) {
            $attachment->set('parentId', $this->id);
            $attachment->set('parentType', 'Email');
            if ($this->entityManager->saveEntity($attachment)) {
                return true;
            }
        }
    }

    public function getBodyPlain()
    {
        $bodyPlain = $this->get('bodyPlain');
        if (!empty($bodyPlain)) {
            return $bodyPlain;
        }

        $body = $this->get('body');

        $breaks = array("<br />","<br>","<br/>","<br />","&lt;br /&gt;","&lt;br/&gt;","&lt;br&gt;");
        $body = str_ireplace($breaks, "\r\n", $body);
        $body = strip_tags($body);
        return $body;
    }

    public function getBodyPlainForSending()
    {
        return $this->getBodyPlain();
    }

    public function getBodyForSending()
    {
        $body = $this->get('body');
        if (!empty($body)) {
            $attachmentList = $this->getInlineAttachments();
            foreach ($attachmentList as $attachment) {
                $body = str_replace("?entryPoint=attachment&amp;id={$attachment->id}", "cid:{$attachment->id}", $body);
            }
        }

        $body = str_replace("<table class=\"table table-bordered\">", "<table class=\"table table-bordered\" width=\"100%\">", $body);

        return $body;
    }

    public function getInlineAttachments()
    {
        $attachmentList = array();
        $body = $this->get('body');
        if (!empty($body)) {
            if (preg_match_all("/\?entryPoint=attachment&amp;id=([^&=\"']+)/", $body, $matches)) {
                if (!empty($matches[1]) && is_array($matches[1])) {
                    foreach($matches[1] as $id) {
                        $attachment = $this->entityManager->getEntity('Attachment', $id);
                        if ($attachment) {
                            $attachmentList[] = $attachment;
                        }
                    }
                }
            }

        }
        return $attachmentList;
    }

    public function getToList()
    {
        $value = $this->get('to');
        if ($value) {
            $arr = explode(';', $value);
            if (is_array($arr)) {
                return $arr;
            }
        }
        return [];
    }

    public function getCcList()
    {
        $value = $this->get('cc');
        if ($value) {
            $arr = explode(';', $value);
            if (is_array($arr)) {
                return $arr;
            }
        }
        return [];
    }

    public function getBccList()
    {
        $value = $this->get('bcc');
        if ($value) {
            $arr = explode(';', $value);
            if (is_array($arr)) {
                return $arr;
            }
        }
        return [];
    }

    public function getReplyToList()
    {
        $value = $this->get('replyTo');
        if ($value) {
            $arr = explode(';', $value);
            if (is_array($arr)) {
                return $arr;
            }
        }
        return [];
    }
}

