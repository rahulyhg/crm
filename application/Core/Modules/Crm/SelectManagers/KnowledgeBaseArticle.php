<?php


namespace Core\Modules\Crm\SelectManagers;

class KnowledgeBaseArticle extends \Core\Core\SelectManagers\Base
{
    protected function filterPublished(&$result)
    {
        $result['whereClause'][] = array(
            'status' => 'Published'
        );
    }

    protected function access(&$result)
    {
        parent::access($result);

        if ($this->checkIsPortal()) {
            $this->filterPublished($result);

            $this->setDistinct(true, $result);
            $this->addLeftJoin('portals', $result);
            $this->addOrWhere(array(
                array(
                    'portals.id' => $this->getUser()->get('portalId')
                )
            ), $result);
        }
    }

    public function applyAdditional(&$result)
    {
        if ($this->checkIsPortal()) {

        }
    }
 }

