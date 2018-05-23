<?php


namespace Core\Services;

use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\NotFound;

use Core\ORM\Entity;

class GlobalSearch extends \Core\Core\Services\Base
{
    protected function init()
    {
        parent::init();
        $this->addDependencyList([
            'entityManager',
            'user',
            'metadata',
            'acl',
            'selectManagerFactory',
            'config'
        ]);
    }

    protected function getSelectManagerFactory()
    {
        return $this->injections['selectManagerFactory'];
    }

    protected function getEntityManager()
    {
        return $this->injections['entityManager'];
    }

    protected function getAcl()
    {
        return $this->injections['acl'];
    }

    protected function getMetadata()
    {
        return $this->injections['metadata'];
    }

    public function find($query, $offset, $maxSize)
    {
        $entityTypeList = $this->getConfig()->get('globalSearchEntityList');

        $unionPartList = [];
        foreach ($entityTypeList as $entityType) {
            if (!$this->getAcl()->checkScope($entityType, 'read')) {
                continue;
            }
            if (!$this->getMetadata()->get('scopes.' . $entityType)) {
                continue;
            }
            $params = array(
                'select' => ['id', 'name', ['VALUE:' . $entityType, 'entityType']]
            );

            $selectManager = $this->getSelectManagerFactory()->create($entityType);
            $selectManager->manageAccess($params);
            $selectManager->manageTextFilter($query, $params);

            $sql = $this->getEntityManager()->getQuery()->createSelectQuery($entityType, $params);

            $unionPartList[] = '' . $sql . '';
        }
        if (empty($unionPartList)) {
            return array(
                'total' => 0,
                'list' => []
            );
        }

        $pdo = $this->getEntityManager()->getPDO();

        $unionSql = implode(' UNION ', $unionPartList);
        $countSql = "SELECT COUNT(*) AS 'COUNT' FROM ({$unionSql}) AS c";
        $sth = $pdo->prepare($countSql);
        $sth->execute();
        $row = $sth->fetch(\PDO::FETCH_ASSOC);
        $totalCount = $row['COUNT'];

        if (count($entityTypeList)) {
            $entityListQuoted = [];
            foreach ($entityTypeList as $entityType) {
                $entityListQuoted[] = $pdo->quote($entityType);
            }
            $unionSql .= " ORDER BY FIELD(entityType, ".implode(', ', $entityListQuoted)."), name";
        } else {
            $unionSql .= " ORDER BY name";
        }
        $unionSql .= " LIMIT :offset, :maxSize";

        $sth = $pdo->prepare($unionSql);

        $sth->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $sth->bindParam(':maxSize', $maxSize, \PDO::PARAM_INT);
        $sth->execute();
        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $entityDataList = [];

        foreach ($rows as $row) {
            $entity = $this->getEntityManager()->getEntity($row['entityType'], $row['id']);
            $entityData = $entity->toArray();
            $entityData['_scope'] = $entity->getEntityType();
            $entityDataList[] = $entityData;
        }

        return array(
            'total' => $totalCount,
            'list' => $entityDataList,
        );
    }
}

