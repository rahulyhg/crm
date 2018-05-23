<?php


namespace Core\Services;

use \Core\ORM\Entity;

use \Core\Core\Exceptions\Error;

class EmailAddress extends Record
{

    protected function findInAddressBookByEntityType($query, $limit, $entityType, &$result)
    {
        $whereClause = array(
            'OR' => array(
                array(
                    'name*' => $query . '%'
                ),
                array(
                    'emailAddress*' => $query . '%'
                )
            ),
            array(
                'emailAddress!=' => null
            )
        );

        $searchParams = array(
            'whereClause' => $whereClause,
            'orderBy' => 'name',
            'limit' => $limit
        );

        $selectManager = $this->getSelectManagerFactory()->create($entityType);

        $selectManager->applyAccess($searchParams);

        $collection = $this->getEntityManager()->getRepository($entityType)->find($searchParams);

        foreach ($collection as $entity) {
            $emailAddress = $entity->get('emailAddress');

            $result[] = array(
                'emailAddress' => $emailAddress,
                'entityName' => $entity->get('name'),
                'entityType' => $entityType,
                'entityId' => $entity->id
            );

            $emailAddressData = $this->getEntityManager()->getRepository('EmailAddress')->getEmailAddressData($entity);
            foreach ($emailAddressData as $d) {
                if ($emailAddress != $d->emailAddress) {
                    $emailAddress = $d->emailAddress;
                    $result[] = array(
                        'emailAddress' => $emailAddress,
                        'entityName' => $entity->get('name'),
                        'entityType' => $entityType,
                        'entityId' => $entity->id
                    );
                    break;
                }
            }
        }
    }

    protected function findInAddressBookUsers($query, $limit, &$result)
    {
        $whereClause = array(
            'OR' => array(
                array(
                    'name*' => $query . '%'
                ),
                array(
                    'emailAddress*' => $query . '%'
                )
            ),
            array(
                'emailAddress!=' => null
            )
        );

        if ($this->getAcl()->get('portalPermission') === 'no') {
            $whereClause['isPortalUser'] = false;
        }

        $searchParams = array(
            'whereClause' => $whereClause,
            'orderBy' => 'name',
            'limit' => $limit
        );

        $selectManager = $this->getSelectManagerFactory()->create('User');

        $selectManager->applyAccess($searchParams);

        $collection = $this->getEntityManager()->getRepository('User')->find($searchParams);

        foreach ($collection as $entity) {
            $emailAddress = $entity->get('emailAddress');

            $result[] = array(
                'emailAddress' => $emailAddress,
                'entityName' => $entity->get('name'),
                'entityType' => 'User',
                'entityId' => $entity->id
            );

            $emailAddressData = $this->getEntityManager()->getRepository('EmailAddress')->getEmailAddressData($entity);
            foreach ($emailAddressData as $d) {
                if ($emailAddress != $d->emailAddress) {
                    $emailAddress = $d->emailAddress;
                    $result[] = array(
                        'emailAddress' => $emailAddress,
                        'entityName' => $entity->get('name'),
                        'entityType' => 'User',
                        'entityId' => $entity->id
                    );
                    break;
                }
            }
        }
    }

    protected function findInInboundEmail($query, $limit, &$result)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $selectParams = [
            'select' => ['id', 'name', 'emailAddress'],
            'whereClause' => [
                'emailAddress*' => $query . '%'
            ],
            'orderBy' => 'name',
        ];
        $qu = $this->getEntityManager()->getQuery()->createSelectQuery('InboundEmail', $selectParams);

        $sth = $pdo->prepare($qu);
        $sth->execute();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = [
                'emailAddress' => $row['emailAddress'],
                'entityName' => $row['name'],
                'entityType' => 'InboundEmail',
                'entityId' => $row['id']
            ];
        }
    }

    public function searchInAddressBook($query, $limit)
    {
        $result = [];

        $this->findInAddressBookUsers($query, $limit, $result);
        if ($this->getAcl()->checkScope('Contact')) {
            $this->findInAddressBookByEntityType($query, $limit, 'Contact', $result);
        }
        if ($this->getAcl()->checkScope('Lead')) {
            $this->findInAddressBookByEntityType($query, $limit, 'Lead', $result);
        }
        if ($this->getAcl()->checkScope('Account')) {
            $this->findInAddressBookByEntityType($query, $limit, 'Account', $result);
        }
        $this->findInInboundEmail($query, $limit, $result);
        foreach ($this->getHavingEmailAddressEntityTypeList() as $entityType) {
            if ($this->getAcl()->checkScope($entityType)) {
                $this->findInAddressBookByEntityType($query, $limit, $entityType, $result);
            }
        }

        $final = array();

        foreach ($result as $r) {
            foreach ($final as $f) {
                if ($f['emailAddress'] == $r['emailAddress']) {
                    continue 2;
                }
            }
            $final[] = $r;
        }

        return $final;
    }

    protected function getHavingEmailAddressEntityTypeList()
    {
        $list = [];
        $scopeDefs = $this->getMetadata()->get(['scopes']);
        foreach ($scopeDefs as $scope => $defs) {
            if (empty($defs['disabled']) && !empty($defs['type']) && ($defs['type'] === 'Person' || $defs['type'] === 'Company')) {
                $list[] = $scope;
            }
        }
        return $list;
    }

}

