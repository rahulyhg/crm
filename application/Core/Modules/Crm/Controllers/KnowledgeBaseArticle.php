<?php


namespace Core\Modules\Crm\Controllers;

class KnowledgeBaseArticle extends \Core\Core\Controllers\Record
{
    public function postActionGetCopiedAttachments($params, $data, $request)
    {
        if (empty($data['id'])) {
            throw new BadRequest();
        }
        $id = $data['id'];

        return $this->getRecordService()->getCopiedAttachments($id);
    }

    public function postActionMoveToTop($params, $data, $request)
    {
        if (empty($data['id'])) {
            throw new BadRequest();
        }
        $where = null;
        if (!empty($data['where'])) {
            $where = $data['where'];
            $where = json_decode(json_encode($where), true);
        }

        $this->getRecordService()->moveToTop($data['id'], $where);

        return true;
    }

    public function postActionMoveUp($params, $data, $request)
    {
        if (empty($data['id'])) {
            throw new BadRequest();
        }
        $where = null;
        if (!empty($data['where'])) {
            $where = $data['where'];
            $where = json_decode(json_encode($where), true);
        }

        $this->getRecordService()->moveUp($data['id'], $where);

        return true;
    }

    public function postActionMoveDown($params, $data, $request)
    {
        if (empty($data['id'])) {
            throw new BadRequest();
        }
        $where = null;
        if (!empty($data['where'])) {
            $where = $data['where'];
            $where = json_decode(json_encode($where), true);
        }

        $this->getRecordService()->moveDown($data['id'], $where);

        return true;
    }

    public function postActionMoveToBottom($params, $data, $request)
    {
        if (empty($data['id'])) {
            throw new BadRequest();
        }
        $where = null;
        if (!empty($data['where'])) {
            $where = $data['where'];
            $where = json_decode(json_encode($where), true);
        }

        $this->getRecordService()->moveToBottom($data['id'], $where);

        return true;
    }
}
