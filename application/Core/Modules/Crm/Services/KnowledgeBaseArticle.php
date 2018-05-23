<?php


namespace Core\Modules\Crm\Services;

use \Core\ORM\Entity;

use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Error;

class KnowledgeBaseArticle extends \Core\Services\Record
{
    protected $readOnlyAttributeList = ['order'];

    protected function init()
    {
        parent::init();
        $this->addDependencyList([
            'fileStorageManager'
        ]);
    }

    protected function getFileStorageManager()
    {
        return $this->getInjection('fileStorageManager');
    }

    public function getCopiedAttachments($id, $parentType = null, $parentId = null)
    {
        $ids = array();
        $names = new \stdClass();

        if (empty($id)) {
            throw new BadRequest();
        }
        $entity = $this->getEntityManager()->getEntity('KnowledgeBaseArticle', $id);
        if (!$entity) {
            throw new NotFound();
        }
        if (!$this->getAcl()->checkEntity($entity, 'read')) {
            throw new Forbidden();
        }
        $entity->loadLinkMultipleField('attachments');
        $attachmentsIds = $entity->get('attachmentsIds');

        foreach ($attachmentsIds as $attachmentId) {
            $source = $this->getEntityManager()->getEntity('Attachment', $attachmentId);
            if ($source) {
                $attachment = $this->getEntityManager()->getEntity('Attachment');
                $attachment->set('role', 'Attachment');
                $attachment->set('type', $source->get('type'));
                $attachment->set('size', $source->get('size'));
                $attachment->set('global', $source->get('global'));
                $attachment->set('name', $source->get('name'));
                $attachment->set('sourceId', $source->getSourceId());
                $attachment->set('storage', $source->get('storage'));

                if (!empty($parentType) && !empty($parentId)) {
                    $attachment->set('parentType', $parentType);
                    $attachment->set('parentId', $parentId);
                }

                if ($this->getFileStorageManager()->isFile($source)) {
                    $this->getEntityManager()->saveEntity($attachment);
                    $contents = $this->getFileStorageManager()->getContents($source);
                    $this->getFileStorageManager()->putContents($attachment, $contents);
                    $ids[] = $attachment->id;
                    $names->{$attachment->id} = $attachment->get('name');
                }
            }
        }

        return array(
            'ids' => $ids,
            'names' => $names
        );
    }

    public function moveUp($id, $where = null)
    {
        $entity = $this->getEntityManager()->getEntity('KnowledgeBaseArticle', $id);
        if (!$entity) throw new NotFound();
        if (!$this->getAcl()->check($entity, 'edit')) throw new Forbidden();

        $currentIndex = $entity->get('order');

        if (!is_int($currentIndex)) throw new Error();

        if (!$where) {
            $where = array();
        }

        $params = array(
            'where' => $where
        );

        $selectManager = $this->getSelectManager();
        $selectParams = $selectManager->buildSelectParams($params, true, true);

        $selectParams['whereClause'][] = array(
            'order<' => $currentIndex
        );

        $selectManager->applyOrder('order', true, $selectParams);

        $previousEntity = $this->getRepository()->findOne($selectParams);

        if (!$previousEntity) return;

        $entity->set('order', $previousEntity->get('order'));
        $previousEntity->set('order', $currentIndex);

        $this->getEntityManager()->saveEntity($entity);
        $this->getEntityManager()->saveEntity($previousEntity);
    }

    public function moveDown($id, $where = null)
    {
        $entity = $this->getEntityManager()->getEntity('KnowledgeBaseArticle', $id);
        if (!$entity) throw new NotFound();
        if (!$this->getAcl()->check($entity, 'edit')) throw new Forbidden();

        $currentIndex = $entity->get('order');

        if (!is_int($currentIndex)) throw new Error();

        if (!$where) {
            $where = array();
        }

        $params = array(
            'where' => $where
        );

        $selectManager = $this->getSelectManager();
        $selectParams = $selectManager->buildSelectParams($params, true, true);

        $selectParams['whereClause'][] = array(
            'order>' => $currentIndex
        );

        $selectManager->applyOrder('order', false, $selectParams);

        $nextEntity = $this->getRepository()->findOne($selectParams);

        if (!$nextEntity) return;

        $entity->set('order', $nextEntity->get('order'));
        $nextEntity->set('order', $currentIndex);

        $this->getEntityManager()->saveEntity($entity);
        $this->getEntityManager()->saveEntity($nextEntity);
    }

    public function moveToTop($id, $where = null)
    {
        $entity = $this->getEntityManager()->getEntity('KnowledgeBaseArticle', $id);
        if (!$entity) throw new NotFound();
        if (!$this->getAcl()->check($entity, 'edit')) throw new Forbidden();

        $currentIndex = $entity->get('order');

        if (!is_int($currentIndex)) throw new Error();

        if (!$where) {
            $where = array();
        }

        $params = array(
            'where' => $where
        );

        $selectManager = $this->getSelectManager();
        $selectParams = $selectManager->buildSelectParams($params, true, true);

        $selectParams['whereClause'][] = array(
            'order<' => $currentIndex
        );

        $selectManager->applyOrder('order', false, $selectParams);

        $previousEntity = $this->getRepository()->findOne($selectParams);

        if (!$previousEntity) return;

        $entity->set('order', $previousEntity->get('order') - 1);

        $this->getEntityManager()->saveEntity($entity);
    }

    public function moveToBottom($id, $where = null)
    {
        $entity = $this->getEntityManager()->getEntity('KnowledgeBaseArticle', $id);
        if (!$entity) throw new NotFound();
        if (!$this->getAcl()->check($entity, 'edit')) throw new Forbidden();

        $currentIndex = $entity->get('order');

        if (!is_int($currentIndex)) throw new Error();

        if (!$where) {
            $where = array();
        }

        $params = array(
            'where' => $where
        );

        $selectManager = $this->getSelectManager();
        $selectParams = $selectManager->buildSelectParams($params, true, true);

        $selectParams['whereClause'][] = array(
            'order>' => $currentIndex
        );

        $selectManager->applyOrder('order', true, $selectParams);

        $nextEntity = $this->getRepository()->findOne($selectParams);

        if (!$nextEntity) return;

        $entity->set('order', $nextEntity->get('order') + 1);

        $this->getEntityManager()->saveEntity($entity);
    }
}
