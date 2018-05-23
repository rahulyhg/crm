<?php


namespace Core\Modules\Crm\Repositories;

use Core\ORM\Entity;

class KnowledgeBaseArticle extends \Core\Core\ORM\Repositories\RDB
{
    protected function beforeSave(Entity $entity, array $options = array())
    {
        parent::beforeSave($entity, $options);
        $order = $entity->get('order');
        if (is_null($order)) {
            $order = $this->min('order');
            if (!$order) {
                $order = 9999;
            }
            $order--;
            $entity->set('order', $order);
        }
    }
}
