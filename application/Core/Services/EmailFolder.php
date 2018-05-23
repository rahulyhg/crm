<?php


namespace Core\Services;

use \Core\ORM\Entity;

use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Error;

class EmailFolder extends Record
{
    protected $systemFolderList = ['inbox', 'important', 'sent'];

    protected $systemFolderEndList = ['drafts', 'trash'];

    protected function init()
    {
        parent::init();
        $this->addDependency('language');
    }

    protected function beforeCreate(Entity $entity, array $data = array())
    {
        parent::beforeCreate($entity, $data);

        if (!$this->getUser()->isAdmin() || !$entity->get('assignedUserId')) {
            $entity->set('assignedUserId', $this->getUser()->id);
        }
        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }
    }

    public function moveUp($id)
    {
        $entity = $this->getEntityManager()->getEntity('EmailFolder', $id);
        if (!$entity) throw new NotFound();
        if (!$this->getAcl()->check($entity, 'edit')) throw new Forbidden();

        $currentIndex = $entity->get('order');

        if (!is_int($currentIndex)) throw new Error();

        $previousEntity = $this->getRepository()->where(array(
            'order<' => $currentIndex,
            'assignedUserId' => $entity->get('assignedUserId')
        ))->order('order', true)->findOne();

        if (!$previousEntity) return;

        $entity->set('order', $previousEntity->get('order'));
        $previousEntity->set('order', $currentIndex);

        $this->getEntityManager()->saveEntity($entity);
        $this->getEntityManager()->saveEntity($previousEntity);
    }

    public function moveDown($id)
    {
        $entity = $this->getEntityManager()->getEntity('EmailFolder', $id);
        if (!$entity) throw new NotFound();
        if (!$this->getAcl()->check($entity, 'edit')) throw new Forbidden();

        $currentIndex = $entity->get('order');

        if (!is_int($currentIndex)) throw new Error();

        $nextEntity = $this->getRepository()->where(array(
            'order>' => $currentIndex,
            'assignedUserId' => $entity->get('assignedUserId')
        ))->order('order', false)->findOne();

        if (!$nextEntity) return;

        $entity->set('order', $nextEntity->get('order'));
        $nextEntity->set('order', $currentIndex);

        $this->getEntityManager()->saveEntity($entity);
        $this->getEntityManager()->saveEntity($nextEntity);
    }

    public function listAll()
    {
        $folderList = $this->getRepository()->where(array(
            'assignedUserId' => $this->getUser()->id
        ))->order('order')->limit(0, 20)->find();

        $list = new \Core\ORM\EntityCollection();

        foreach ($this->systemFolderList as $name) {
            $folder = $this->getEntityManager()->getEntity('EmailFolder');
            $folder->set('name', $this->getInjection('language')->translate($name, 'presetFilters', 'Email'));
            $folder->id = $name;
            $list[] = $folder;
        }


        foreach ($folderList as $folder) {
            $list[] = $folder;
        }

        foreach ($this->systemFolderEndList as $name) {
            $folder = $this->getEntityManager()->getEntity('EmailFolder');
            $folder->set('name', $this->getInjection('language')->translate($name, 'presetFilters', 'Email'));
            $folder->id = $name;
            $list[] = $folder;
        }

        $finalList = [];
        foreach ($list as $item) {
            $attributes = $item->getValues();
            $attributes['childCollection'] = [];
            $finalList[] = $attributes;
        }

        return array(
            'list' => $finalList
        );
    }
}

