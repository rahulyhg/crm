<?php


namespace Core\Modules\Crm\Services;

use \Core\ORM\Entity;

class KnowledgeBaseCategory extends \Core\Services\RecordTree
{
    protected function checkFilterOnlyNotEmpty()
    {
        if (!$this->getAcl()->checkScope('KnowledgeBaseArticle', 'create')) {
            return true;
        }
    }

    protected function checkItemIsEmpty(Entity $entity)
    {
        $selectManager = $this->getSelectManager('KnowledgeBaseArticle');

        $selectParams = $selectManager->getEmptySelectParams();
        $selectManager->applyInCategory('categories', $entity->id, $selectParams);
        $selectManager->applyAccess($selectParams);

        if ($this->getEntityManager()->getRepository('KnowledgeBaseArticle')->findOne($selectParams)) {
            return false;
        }
        return true;
    }
}

