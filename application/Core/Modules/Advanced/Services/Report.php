<?php
/*********************************************************************************
 * The contents of this file are subject to the CoreCRM Advanced
 * Agreement ("License") which can be viewed at
 * http://www.espocrm.com/advanced-pack-agreement.
 * By installing or using this file, You have unconditionally agreed to the
 * terms and conditions of the License, and You may not use this file except in
 * compliance with the License.  Under the terms of the license, You shall not,
 * sublicense, resell, rent, lease, distribute, or otherwise  transfer rights
 * or usage to the software.
 * 
 * License ID: bcac485dee9efd0f36cf6842ad5b69b4
 ***********************************************************************************/

namespace Core\Modules\Advanced\Services;

use \Core\ORM\Entity;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Forbidden;

class Report extends \Core\Services\Record
{
    protected function init()
    {
        parent::init();
        $this->addDependency('language');
        $this->addDependency('container');
        $this->addDependency('acl');
        $this->addDependency('preferences');
        $this->addDependency('config');
        $this->addDependency('user');
        $this->addDependency('serviceFactory');
        $this->addDependency('formulaManager');
        $this->addDependency('injectableFactory');
    }

    protected function getPreferences()
    {
        return $this->injections['preferences'];
    }

    protected function getServiceFactory()
    {
        return $this->injections['serviceFactory'];
    }

    protected function getConfig()
    {
        return $this->injections['config'];
    }

    protected function getUser()
    {
        return $this->injections['user'];
    }

    protected function getLanguage()
    {
        return $this->injections['language'];
    }

    protected function getAcl()
    {
        return $this->injections['acl'];
    }

    protected function getFormulaManager()
    {
        return $this->getInjection('formulaManager');
    }

    protected function getContainer()
    {
        return $this->injections['container'];
    }

    protected function getRecordService($name)
    {
        if ($this->getServiceFactory()->checkExists($name)) {
            $service = $this->getServiceFactory()->create($name);
            $service->setEntityType($name);
        } else {
            $service = $this->getServiceFactory()->create('Record');
            if (method_exists($service, 'setEntityType')) {
                $service->setEntityType($name);
            } else {
                $service->setEntityName($name);
            }
        }

        return $service;
    }

    protected function beforeCreate(Entity $entity, array $data = array())
    {
        parent::beforeCreate($entity, $data);
        if (!$this->getAcl()->check($entity->get('entityType'), 'read')) {
            throw new Forbidden();
        }
    }

    protected function beforeUpdate(Entity $entity, array $data = array())
    {
        parent::beforeUpdate($entity, $data);
        $entity->clear('entityType');
    }

    public function getInternalReportImpl(Entity $report)
    {
        $className = $report->get('internalClassName');
        if (!empty($className)) {
            if (stripos($className, ':') !== false) {
                list($moduleName, $reportName) = explode(':', $className);
                if ($moduleName == 'Custom') {
                    $className = "\\Core\\Custom\\Reports\\{$reportName}";
                } else {
                    $className = "\\Core\\Modules\\{$moduleName}\\Reports\\{$reportName}";
                }
            } else {
                $className = "\\Core\\Reports\\{$className}";
            }
        } else {
            throw new Error('No class name specified for internal report.');
        }
        $reportObj = new $className($this->getContainer());

        return $reportObj;
    }

    public function fetchDataFromReport(Entity $report)
    {
        $data = $report->get('data');
        if (empty($data)) {
            $data = new \StdClass();
        }
        $data->orderBy = $report->get('orderBy');
        $data->groupBy = $report->get('groupBy');
        $data->columns = $report->get('columns');

        if ($report->get('filtersData') && !$report->get('filtersDataList')) {
            $data->filtersWhere = $this->convertFiltersData($report->get('filtersData'));
        } else {
            $data->filtersWhere = $this->convertFiltersDataList($report->get('filtersDataList'));
        }

        $data->chartColors = $report->get('chartColors');
        $data->chartColor = $report->get('chartColor');

        return $data;
    }

    public function checkReportIsPosibleToRun(Entity $report)
    {
        if (in_array($report->get('entityType'), $this->getMetadata()->get('entityDefs.Report.entityListToIgnore', []))) {
            throw new Forbidden();
        }
    }

    public function run($id, $where = null, array $params = null, $additionalParams = array())
    {
        if (empty($id)) {
            throw new Error();
        }
        $report = $this->getEntity($id);

        if (!$report) {
            throw new NotFound();
        }

        if (!$this->getAcl()->check($report, 'read')) {
            throw new Forbidden();
        }

        if ($report->get('isInternal')) {
            $reportObj = $this->getInternalReportImpl($report);
            return $reportObj->run($where, $params);
        }

        $type = $report->get('type');

        $entityType = $report->get('entityType');

        $data = $this->fetchDataFromReport($report);

        if (!$this->getAcl()->check($entityType, 'read')) {
            throw new Forbidden();
        }

        $this->checkReportIsPosibleToRun($report);

        switch ($type) {
            case 'Grid':
                if (!empty($params) && is_array($params) && array_key_exists('groupValue', $params)) {
                    return $this->executeSubReport($entityType, $data, $where, $params);
                }
                return $this->executeGridReport($entityType, $data, $where);
            case 'List':
                return $this->executeListReport($entityType, $data, $where, $params, $additionalParams);
        }
    }

    protected function convertFiltersDataList($filtersDataList)
    {
        if (empty($filtersDataList)) {
            return null;
        }

        $arr = [];

        foreach ($filtersDataList as $defs) {
            $field = null;
            if (isset($defs->name)) {
                $field = $defs->name;
            }

            if (empty($defs) || empty($defs->params)) {
                continue;
            }

            $params = $defs->params;

            if (!empty($defs->type) && in_array($defs->type, ['or', 'and', 'not'])) {
                if (empty($params->value)) continue;

                $o = new \StdClass();
                $o->type = $params->type;
                $o->value = $this->convertFiltersDataList($params->value);

                $arr[] = $o;

            } else if (!empty($defs->type) && $defs->type === 'complexExpression') {
                if (empty($params->attribute)) continue;

                $o = new \StdClass();

                if (isset($params->attribute)) {
                    $o->attribute = $params->attribute;
                    if (isset($params->function)) {
                        $o->attribute = $params->function . ':' . $o->attribute;
                    }
                }
                if (isset($params->operator)) {
                    $o->type = $params->operator;
                }

                if (isset($params->value) && is_string($params->value) && strlen($params->value)) {
                    try {
                        $o->value = $this->getFormulaManager()->run($params->value);
                    } catch (Error $e) {
                        throw new Error('Error in formula expression');
                    }
                }

                $arr[] = $o;
            } else {
                if (isset($params->where)) {
                    $arr[] = $params->where;
                } else {
                    if (isset($params->field)) {
                        $field = $params->field;
                    }
                    if (!empty($params->type)) {
                        $type = $params->type;
                        if (!empty($params->dateTime)) {
                            $arr[] = $this->convertDateTimeWhere($type, $field, isset($params->value) ? $params->value : null);
                        } else {
                            $o = new \StdClass();
                            $o->type = $type;
                            $o->field = $field;
                            $o->attribute = $field;
                            $o->value = isset($params->value) ? $params->value : null;
                            $arr[] = $o;
                        }
                    }
                }
            }
        }

        return $arr;

    }

    protected function convertFiltersData($filtersData)
    {
        if (empty($filtersData)) {
            return null;
        }

        $arr = [];

        foreach ($filtersData as $name => $defs) {
            $field = $name;

            if (empty($defs)) {
                continue;
            }

            if (isset($defs->where)) {
                $arr[] = $defs->where;
            } else {
                if (isset($defs->field)) {
                    $field = $defs->field;
                }
                $type = $defs->type;
                if (!empty($defs->dateTime)) {
                    $arr[] = $this->convertDateTimeWhere($type, $field, isset($defs->value) ? $defs->value : null);
                } else {
                    $o = new \StdClass();
                    $o->type = $type;
                    $o->field = $field;
                    $o->value = $defs->value;
                    $arr[] = $o;
                }
            }
        }

        return $arr;
    }

    protected function convertDateTimeWhere($type, $field, $value)
    {
        $where = new \StdClass();
        $where->field = $field;

        $format = 'Y-m-d H:i:s';

        if (empty($value) && in_array($type, array('on', 'before', 'after'))) {
            return null;
        }

        $timeZone = $this->getPreferences()->get('timeZone');
        if (empty($timeZone)) {
            $timeZone = $this->getConfig()->get('timeZone');
        }

        $dt = new \DateTime('now', new \DateTimeZone($timeZone));

        switch ($type) {
            case 'today':
                $where->type = 'between';
                $dt->setTime(0, 0, 0);
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $from = $dt->format($format);
                $dt->modify('+1 day');
                $to = $dt->format($format);
                $where->value = [$from, $to];
                break;
            case 'past':
                $where->type = 'before';
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $where->value = $dt->format($format);
                break;
            case 'future':
                $where->type = 'after';
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $where->value = $dt->format($format);
                break;
            case 'lastSevenDays':
                $where->type = 'between';

                $dtFrom = clone $dt;

                $dt->setTimezone(new \DateTimeZone('UTC'));
                $to = $dt->format($format);


                $dtFrom->modify('-7 day');
                $dtFrom->setTime(0, 0, 0);
                $dtFrom->setTimezone(new \DateTimeZone('UTC'));

                $from = $dtFrom->format($format);

                $where->value = [$from, $to];

                break;
            case 'lastXDays':
                $where->type = 'between';

                $dtFrom = clone $dt;

                $dt->setTimezone(new \DateTimeZone('UTC'));
                $to = $dt->format($format);

                $number = strval(intval($value));
                $dtFrom->modify('-'.$number.' day');
                $dtFrom->setTime(0, 0, 0);
                $dtFrom->setTimezone(new \DateTimeZone('UTC'));

                $from = $dtFrom->format($format);

                $where->value = [$from, $to];

                break;
            case 'nextXDays':
                $where->type = 'between';

                $dtTo = clone $dt;

                $dt->setTimezone(new \DateTimeZone('UTC'));
                $from = $dt->format($format);

                $number = strval(intval($value));
                $dtTo->modify('+'.$number.' day');
                $dtTo->setTime(24, 59, 59);
                $dtTo->setTimezone(new \DateTimeZone('UTC'));

                $to = $dtTo->format($format);

                $where->value = [$from, $to];

                break;
            case 'nextXDays':
                $where->type = 'between';

                $dtTo = clone $dt;

                $dt->setTimezone(new \DateTimeZone('UTC'));
                $from = $dt->format($format);

                $number = strval(intval($value));
                $dtTo->modify('+'.$number.' day');
                $dtTo->setTime(24, 59, 59);
                $dtTo->setTimezone(new \DateTimeZone('UTC'));

                $to = $dtTo->format($format);

                $where->value = [$from, $to];

                break;
            case 'olderThanXDays':
                $where->type = 'before';
                $number = strval(intval($value));
                $dt->modify('-'.$number.' day');
                $dt->setTime(0, 0, 0);
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $where->value = $dt->format($format);
                break;
            case 'on':
                $where->type = 'between';

                $dt = new \DateTime($value, new \DateTimeZone($timeZone));
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $from = $dt->format($format);

                $dt->modify('+1 day');
                $to = $dt->format($format);
                $where->value = [$from, $to];
                break;
            case 'before':
                $where->type = 'before';
                $dt = new \DateTime($value, new \DateTimeZone($timeZone));
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $where->value = $dt->format($format);
                break;
            case 'after':
                $where->type = 'after';
                $dt = new \DateTime($value, new \DateTimeZone($timeZone));
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $where->value = $dt->format($format);
                break;
            case 'between':
                $where->type = 'between';
                if (is_array($value)) {
                    $dt = new \DateTime($value[0], new \DateTimeZone($timeZone));
                    $dt->setTimezone(new \DateTimeZone('UTC'));
                    $from = $dt->format($format);

                    $dt = new \DateTime($value[1], new \DateTimeZone($timeZone));
                    $dt->setTimezone(new \DateTimeZone('UTC'));
                    $to = $dt->format($format);

                    $where->value = [$from, $to];
                }
               break;
            default:
                $where->type = $type;
        }

        return $where;
    }

    protected function handleLeftJoins($item, $entityType, &$params)
    {
        if (strpos($item, ':') !== false) {
            list($f, $item) = explode(':', $item);
        }

        if (strpos($item, '.') !== false) {
            list($rel, $f) = explode('.', $item);
            if (!in_array($rel, $params['leftJoins'])) {
                $params['leftJoins'][] = $rel;
                $defs = $this->getEntityManager()->getMetadata()->get($entityType);
                if (!empty($defs['relations']) && !empty($defs['relations'][$rel])) {
                    $params['distinct'] = true;
                }
            }
        } else {
            $defs = $this->getEntityManager()->getMetadata()->get($entityType);
            if (!empty($defs['fields']) && !empty($defs['fields'][$item]) && !empty($defs['fields'][$item]['type'])) {
                $type = $defs['fields'][$item]['type'];
                if ($type === 'foreign') {
                    if (!empty($defs['fields'][$item]['relation'])) {
                        $relation = $defs['fields'][$item]['relation'];
                        if (!in_array($relation, $params['leftJoins'])) {
                            $params['leftJoins'][] = $relation;
                        }
                    }
                }
            }
        }
    }

    protected function handleGroupBy($groupBy, $entityType, &$params, &$linkColumns, &$groupNameMap)
    {
        foreach ($groupBy as $item) {
            if (strpos($item, '.') === false) {
                $fieldType = $this->getMetadata()->get('entityDefs.' . $entityType . '.fields.' . $item . '.type');
                if (in_array($fieldType, ['link', 'file', 'image'])) {
                    if (!in_array($item, $params['leftJoins'])) {
                        $params['leftJoins'][] = $item;
                    }
                    $params['select'][] = $item . 'Name';
                    $params['select'][] = $item . 'Id';
                    $params['groupBy'][] = $item . 'Id';

                    $linkColumns[] = $item;
                } else if ($fieldType == 'linkParent') {
                    if (!in_array($item, $params['leftJoins'])) {
                        $params['leftJoins'][] = $item;
                    }
                    $params['select'][] = $item . 'Type';
                    $params['select'][] = $item . 'Id';
                    $params['groupBy'][] = $item . 'Id';
                    $params['groupBy'][] = $item . 'Type';
                } else {
                    if ($fieldType == 'enum') {
                        $groupNameMap[$item] = $this->getLanguage()->translate($item, 'options', $entityType);
                    }

                    $params['select'][] = $item;
                    $params['groupBy'][] = $item;
                }
            } else {
                $a = explode('.', $item);
                $link = $a[0];
                $field = $a[1];

                $skipSelect = false;

                $defs = $this->getEntityManager()->getMetadata()->get($entityType);
                if (!empty($defs['relations']) && !empty($defs['relations'][$link])) {
                    $type = $defs['relations'][$link]['type'];
                    $foreignScope = $defs['relations'][$link]['entity'];

                    $foreignDefs = $this->getEntityManager()->getMetadata()->get($foreignScope);

                    if (!empty($foreignDefs['relations']) && !empty($foreignDefs['relations'][$field])) {
                        $foreignType = $foreignDefs['relations'][$field]['type'];

                        if ($type === 'belongsTo') {
                            $params['select'][] = $item . 'Id';
                            $params['groupBy'][] = $item . 'Id';
                            $skipSelect = true;
                            $linkColumns[] = $item;
                        }
                    }
                }

                $this->handleLeftJoins($item, $entityType, $params);
                if (!$skipSelect) {
                    $params['select'][] = $item;
                    $params['groupBy'][] = $item;
                }
            }
        }
    }

    protected function handleColumns($columns, $entityType, &$params, &$linkColumns)
    {
        foreach ($columns as $item) {
            if (strpos($item, '.') === false) {
                $type = $this->getMetadata()->get('entityDefs.' . $entityType . '.fields.' . $item . '.type');
                 if (in_array($type, ['link', 'file', 'image'])) {
                    if (!in_array($item, $params['leftJoins'])) {
                        $params['leftJoins'][] = $item;
                    }
                    if (!in_array($item . 'Name', $params['select'])) {
                        $params['select'][] = $item . 'Name';
                    }
                    if (!in_array($item . 'Id', $params['select'])) {
                        $params['select'][] = $item . 'Id';
                    }
                    $linkColumns[] = $item;
                } else if ($type == 'linkParent') {
                    if (!in_array($item . 'Id', $params['select'])) {
                        $params['select'][] = $item . 'Id';
                    }
                    if (!in_array($item . 'Type', $params['select'])) {
                        $params['select'][] = $item . 'Type';
                    }
                    $linkColumns[] = $item;
                } else if ($type == 'currency') {
                    if (!in_array($item, $params['select'])) {
                        $params['select'][] = $item;
                    }
                    if (!in_array($item . 'Currency', $params['select'])) {
                        $params['select'][] = $item . 'Currency';
                    }
                    if (!in_array($item . 'Converted', $params['select'])) {
                        $params['select'][] = $item . 'Converted';
                    }
                } else if ($type == 'duration') {
                    $start = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', $item, 'start']);
                    $end = $this->getMetadata()->get(['entityDefs', $entityType , 'fields', $item, 'end']);
                    if (!in_array($start, $params['select'])) {
                        $params['select'][] = $start;
                    }
                    if (!in_array($end, $params['select'])) {
                        $params['select'][] = $end;
                    }
                    if (!in_array($item, $params['select'])) {
                        $params['select'][] = $item;
                    }
                } else if ($type == 'personName') {
                    if (!in_array($item, $params['select'])) {
                        $params['select'][] = $item;
                    }
                    if (!in_array('first' . ucfirst($item), $params['select'])) {
                        $params['select'][] = 'first' . ucfirst($item);
                    }
                    if (!in_array('last' . ucfirst($item), $params['select'])) {
                        $params['select'][] = 'last' . ucfirst($item);
                    }
                } else {
                    if (!in_array($item, $params['select'])) {
                        $params['select'][] = $item;
                    }
                }
            } else {
                $columnList = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', $item, 'columnList']);
                if ($columnList) {
                    foreach ($columnList as $column) {
                        if (!in_array($column, $params['select'])) {
                            $params['select'][] = $column;
                        }
                    }
                } else {
                    $this->handleLeftJoins($item, $entityType, $params);

                    $a = explode('.', $item);
                    $link = $a[0];
                    $field = $a[1];

                    $skipSelect = false;

                    $foreignType = $this->getForeignFieldType($entityType, $link, $field);
                    if (in_array($foreignType, ['link', 'file', 'image'])) {
                        if (!in_array($item, $params['leftJoins'])) {
                            $params['select'][] = $item . 'Id';
                            $skipSelect = true;
                        }
                    }

                    if (!$skipSelect && !in_array($item, $params['select'])) {
                        $params['select'][] = $item;
                    }
                }

            }
        }
    }

    protected function getForeignFieldType($entityType, $link, $field)
    {
        $defs = $this->getEntityManager()->getMetadata()->get($entityType);
        if (!empty($defs['relations']) && !empty($defs['relations'][$link])) {
            $foreignScope = $defs['relations'][$link]['entity'];
            $foreignType = $this->getMetadata()->get(['entityDefs', $foreignScope, 'fields', $field, 'type']);
            return $foreignType;
            if (in_array($foreignType, ['link', 'file', 'image'])) {
                if (!in_array($item, $params['leftJoins'])) {
                    $params['select'][] = $item . 'Id';
                    $skipSelect = true;
                }
            }
        }
    }

    protected function handleOrderBy($orderBy, $entityType, &$params, &$orderLists)
    {
        foreach ($orderBy as $item) {
            if (strpos($item, 'LIST:') !== false) {
                $orderBy = substr($item, 5);

                if (strpos($orderBy, '.') !== false) {
                    list($rel, $field) = explode('.', $orderBy);

                    $foreignEntity = $this->getMetadata()->get('entityDefs.' . $entityType . '.links.' . $rel . '.entity');
                    if (empty($foreignEntity)) {
                        continue;
                    }
                    $options = $this->getMetadata()->get('entityDefs.' . $foreignEntity . '.fields.' . $field . '.options',  array());
                } else {
                    $field = $orderBy;
                    $options = $this->getMetadata()->get('entityDefs.' . $entityType . '.fields.' . $field . '.options',  array());
                }

                $params['orderBy'][] = array(
                    'LIST:' . $orderBy . ':' . implode(',', $options),
                );
                $orderLists[$orderBy] = $options;
            } else {
                if (strpos($item, 'ASC:') !== false) {
                    $orderBy = substr($item, 4);
                    $order = 'ASC';
                } else if (strpos($item, 'DESC:') !== false) {
                    $orderBy = substr($item, 5);
                    $order = 'DESC';
                } else {
                    continue;
                }

                $fieldType = $this->getMetadata()->get('entityDefs.' . $entityType . '.fields.' . $orderBy . '.type');

                if (in_array($fieldType, ['link', 'file', 'image'])) {
                    $orderBy = $orderBy . 'Name';
                } else if ($fieldType === 'linkParent') {
                    $orderBy = $orderBy . 'Type';
                }

                if (!in_array($orderBy, $params['select'])) {
                    continue;
                }

                $index = array_search($orderBy, $params['select']) + 1;

                $params['orderBy'][] = array(
                    $index,
                    $order
                );
            }
        }
    }

    protected function handleFilters($where, $entityType, &$params, $isGrid = false)
    {
        foreach ($where as $item) {
            $this->handleWhereItem($item, $entityType, $params);
        }

        $selectManager = $this->getSelectManagerFactory()->create($entityType);
        $filtersParams = $selectManager->getSelectParams(array('where' => $where));

        $params = $this->mergeSelectParams($params, $filtersParams, $entityType, $isGrid);
    }

    protected function handleWhereItem($item, $entityType, &$params)
    {
        if (!empty($item['type'])) {
            if (in_array($item['type'], ['or', 'and', 'not'])) {
                if (!array_key_exists('value', $item) || !is_array($item['value'])) return;
                foreach ($item['value'] as $listItem) {
                    $this->handleWhereItem($listItem, $entityType, $params);
                }
                return;
            }
        }



        $attibute = null;
        if (!empty($item['field'])) {
            $attibute = $item['field'];
        }
        if (!empty($item['attribute'])) {
            $attibute = $item['attribute'];
        }

        if ($attibute) {
            $this->handleLeftJoins($attibute, $entityType, $params);
        }
    }

    protected function handleWhere($where, $entityType, &$params, $isGrid = false)
    {
        foreach ($where as $item) {
            $this->handleWhereItem($item, $entityType, $params);
        }

        $selectManager = $this->getSelectManagerFactory()->create($entityType);
        $filtersParams = $selectManager->getSelectParams(array('where' => $where));

        $params = $this->mergeSelectParams($params, $filtersParams, $entityType, $isGrid);
    }

    protected function mergeSelectParams($params1, $params2, $entityType, $isGrid = false)
    {
        $selectManager = $this->getSelectManagerFactory()->create($entityType);

        $customWhere = '';
        if (!empty($params1['customWhere'])) {
            $customWhere .= $params1['customWhere'];
        }
        if (!empty($params2['customWhere'])) {
            $customWhere .= $params2['customWhere'];
        }

        $customJoin = '';
        if (!empty($params1['customJoin'])) {
            $customJoin .= $params1['customJoin'];
        }
        if (!empty($params2['customJoin'])) {
            $customJoin .= $params2['customJoin'];
        }

        foreach ($params2['joins'] as $join) {
            $selectManager->addJoin($join, $params1);
        }
        foreach ($params2['leftJoins'] as $join) {
            $selectManager->addLeftJoin($join, $params1);
        }
        if ($isGrid) {
            unset($params2['additionalSelectColumns']);
        }

        unset($params2['joins']);
        unset($params2['leftJoins']);

        $result = array_replace_recursive($params2, $params1);

        $result['customWhere'] = $customWhere;
        $result['customJoin'] = $customJoin;

        return $result;
    }

    public function fetchSelectParamsFromListReport(Entity $report)
    {
        $data = $this->fetchDataFromReport($report);
        $params = $this->prepareListReportSelectParams($report->get('entityType'), $data);

        return $params;
    }

    public function prepareListReportSelectParams($entityType, $data, $where = null, array $rawParams = null)
    {
        if (empty($rawParams)) {
            $rawParams = array();
        }

        $selectManager = $this->getSelectManagerFactory()->create($entityType);

        $params = $selectManager->getSelectParams($rawParams);

        if (!empty($data->columns)) {
            $params['select'] = [];
            $linkColumns = [];
            $this->handleColumns($data->columns, $entityType, $params, $linkColumns);
            $params['select'][] = 'id';
        }

        if (!empty($data->filtersWhere)) {
            $filtersWhere = json_decode(json_encode($data->filtersWhere), true);
            $this->handleFilters($filtersWhere, $entityType, $params);
        }

        if ($rawParams && !empty($rawParams['sortBy'])) {

            $fieldType = $this->getMetadata()->get('entityDefs.' . $entityType . '.fields.' . $rawParams['sortBy'] . '.type');
            if (in_array($fieldType, ['link', 'file', 'image'])) {
                $selectManager->addLeftJoin($rawParams['sortBy'], $params);
            }

            if (in_array($fieldType, ['link', 'file', 'image'])) {
                $sortField = $rawParams['sortBy'] . 'Name';
            } else if ($fieldType === 'linkParent') {
                $sortField = $rawParams['sortBy'] . 'Type';
            } else {
                $sortField = $rawParams['sortBy'];
            }

            if (
                array_key_exists('select', $params) &&
                is_array($params['select']) &&
                !in_array($sortField, $params['select'])
            ) {
                $params['select'][] = $sortField;
            }
        }

        if ($where) {
            $this->handleWhere($where, $entityType, $params);
        }

        return $params;
    }

    protected function executeListReport($entityType, $data, $where = null, array $rawParams = null, $additionalParams = array())
    {
        if (!empty($additionalParams['customColumnList']) && is_array($additionalParams['customColumnList'])) {
            $initialColumnList = $data->columns;
            $newColumnList = [];
            foreach ($additionalParams['customColumnList'] as $item) {
                if (strpos($item, '.') !== false) {
                    if (!in_array($item, $initialColumnList)) {
                        break;
                    }
                }
                $newColumnList[] = $item;
            }

            $data->columns = $newColumnList;
        }

        $params = $this->prepareListReportSelectParams($entityType, $data, $where, $rawParams);

        $paramsCopied = $params;

        $this->getEntityManager()->getRepository($entityType)->handleSelectParams($paramsCopied);

        if (!empty($additionalParams['fullSelect'])) {
            unset($paramsCopied['select']);
        }

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery($entityType, $paramsCopied);

        $additionalFieldDefs = array();

        foreach ($data->columns as $column) {
            if (strpos($column, '.') === false) continue;
            $arr = explode('.', $column);
            $link = $arr[0];
            $attribute = $arr[1];

            $foreignAttribute = $link . '_' . $attribute;

            $foreignType = $this->getForeignFieldType($entityType, $link, $attribute);
            if (in_array($foreignType, ['image', 'file', 'link'])) {
                $additionalFieldDefs[$foreignAttribute . 'Id'] = array(
                    'type' => 'foreign'
                );
            } else {
                $additionalFieldDefs[$foreignAttribute] = array(
                    'type' => 'foreign'
                );
            }
        }

        $count = $this->getEntityManager()->getRepository($entityType)->count($params);

        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $dataList = [];
        foreach ($rows as $i => $row) {
            $data = array();
            foreach ($row as $attr => $value) {
                $attribute = str_replace('.', '_', $attr);
                $data[$attribute] = $value;
            }
            $dataList[] = $data;
        }

        $service = $this->getRecordService($entityType);
        $collection = $this->getEntityManager()->createCollection($entityType);

        foreach ($dataList as $data) {
            $entity = $this->getEntityManager()->getEntity($entityType);

            $fieldDefs = $entity->getAttributes();
            $fieldDefs = array_merge($fieldDefs, $additionalFieldDefs);
            $entity->fields = $fieldDefs;

            $entity->set($data);

            $service->loadAdditionalFieldsForList($entity);
            $collection[] = $entity;
        }

        return array(
            'collection' => $collection,
            'total' => $count
        );
    }

    protected function executeSubReport($entityType, $data, $where, array $rawParams)
    {
        $groupValue = $rawParams['groupValue'];
        unset($rawParams['groupValue']);

        $selectManager = $this->getSelectManagerFactory()->create($entityType);

        $params = $selectManager->getSelectParams($rawParams);

        $params['whereClause'] = isset($params['whereClause']) ? $params['whereClause'] : array();
        $params['leftJoins'] = isset($params['leftJoins']) ? $params['leftJoins'] : array();

        if (!empty($data->groupBy)) {
            $this->handleGroupBy($data->groupBy, $entityType, $params, $linkColumns, $groupNameMap);
            $groupBy = $params['groupBy'][0];
            unset($params['groupBy']);
        }

        if (empty($groupBy)) {
            throw new Error();
        }

        unset($params['select']);

        if (!empty($data->filtersWhere)) {
            $filtersWhere = json_decode(json_encode($data->filtersWhere), true);
            $this->handleFilters($filtersWhere, $entityType, $params);
        }

        if ($where) {
            $this->handleWhere($where, $entityType, $params);
        }

        $params['whereClause'] = (!empty($params['whereClause'])) ? $params['whereClause'] : array();

        if ($this->getMetadata()->get('entityDefs.' . $entityType . '.fields.' . $data->groupBy[0] . '.type') == 'linkParent') {
            $arr = explode(':,:', $groupValue);

            $valueType = $arr[0];
            $valueId = null;
            if (count($arr)) {
                $valueId = $arr[1];
            }
            if (empty($valueId)) {
                $valueId = null;
            }
            $params['whereClause'][$data->groupBy[0]. 'Type'] = $valueType;
            $params['whereClause'][$data->groupBy[0]. 'Id'] = $valueId;
        } else {
            $params['whereClause'][$groupBy] = $groupValue;
        }

        $collection = $this->getEntityManager()->getRepository($entityType)->find($params);
        $count = $this->getEntityManager()->getRepository($entityType)->count($params);

        $service = $this->getRecordService($entityType);
        foreach ($collection as $entity) {
            $service->loadAdditionalFieldsForList($entity);
        }

        return array(
            'collection' => $collection,
            'total' => $count
        );
    }

    protected function executeGridReport($entityType, $data, $where)
    {
        $params = array();

        $seed = $this->getEntityManager()->getEntity($entityType);

        $this->getEntityManager()->getRepository($entityType)->handleSelectParams($params);

        $params['select'] = [];
        $params['groupBy'] = [];
        $params['orderBy'] = [];
        $params['whereClause'] = array();
        $params['leftJoins'] = isset($params['leftJoins']) ? $params['leftJoins'] : [];

        $params['additionalSelectColumns'] = [];

        $groupNameMap = array();
        $orderLists = array();
        $linkColumns = array();
        $sums = array();

        if (!empty($data->groupBy)) {
            $this->handleGroupBy($data->groupBy, $entityType, $params, $linkColumns, $groupNameMap);
        }

        if (!empty($data->columns)) {
            $this->handleColumns($data->columns, $entityType, $params, $linkColumns);
        }

        if (!empty($data->orderBy)) {
            $this->handleOrderBy($data->orderBy, $entityType, $params, $orderLists);
        }

        if (!empty($data->filtersWhere)) {
            $filtersWhere = json_decode(json_encode($data->filtersWhere), true);
            $this->handleFilters($filtersWhere, $entityType, $params, true);
        }

        if ($where) {
            $this->handleWhere($where, $entityType, $params, true);
        }

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery($entityType, $params);

        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $i => $row) {
            foreach ($row as $j => $value) {
                if (is_null($value)) {
                    $rows[$i][$j] = '';
                    unset($rows[$i]);
                }
            }
        }

        foreach ($data->groupBy as $groupByItem) {
            if ($this->getMetadata()->get('entityDefs.' . $entityType . '.fields.' . $groupByItem . '.type') == 'linkParent') {
                $this->mergeGroupByColumns($rows, $params['groupBy'], $groupByItem, [$groupByItem . 'Type', $groupByItem . 'Id']);
                $groupNameMap[$groupByItem] = array();

                foreach ($rows as $row) {
                    $itemCompositeValue = $row[$groupByItem];
                    $arr = explode(':,:', $itemCompositeValue);
                    $itemValue = '';
                    if (count($arr)) {
                        $itemEntity = $this->getEntityManager()->getEntity($arr[0], $arr[1]);
                        if ($itemEntity) {
                            $itemValue = $this->getLanguage()->translate($arr[0], 'scopeNames') . ': ' . $itemEntity->get('name');
                        }
                    }
                    $groupNameMap[$groupByItem][$row[$groupByItem]] = $itemValue;
                }
            }
        }

        $grouping = array();
        foreach ($params['groupBy'] as $i => $groupCol) {
            $grouping[$i] = array();
            foreach ($rows as $row) {
                if (!in_array($row[$groupCol], $grouping[$i])) {
                    $grouping[$i][] = $row[$groupCol];
                }
            }
            if ($i > 0) {
                if (in_array('ASC:' . $groupCol, $data->orderBy)) {
                    sort($grouping[$i]);
                } if (in_array('DESC:' . $groupCol, $data->orderBy)) {
                    rsort($grouping[$i]);
                } else if (in_array('LIST:' . $groupCol, $data->orderBy)) {
                    if (!empty($orderLists[$groupCol])) {
                        $list = $orderLists[$groupCol];
                        usort($grouping[$i], function ($a, $b) use ($list) {
                            return array_search($a, $list) > array_search($b, $list);
                        });
                    }
                }
            }

            $isDate = false;
            if (strpos($groupCol, 'MONTH:') === 0) {
                $isDate = true;
                sort($grouping[$i]);
                $fullList = array();

                if (isset($grouping[$i][0]) && isset($grouping[$i][count($grouping[$i])  - 1])) {
                    $dt = new \DateTime($grouping[$i][0] . '-01');
                    $dtEnd = new \DateTime($grouping[$i][count($grouping[$i])  - 1] . '-01');
                    if ($dt && $dtEnd) {
                        $interval = new \DateInterval('P1M');
                        while ($dt->getTimestamp() <= $dtEnd->getTimestamp()) {
                            $fullList[] = $dt->format('Y-m');
                            $dt->add($interval);
                        }
                        $grouping[$i] = $fullList;
                    }
                }
            } else if (strpos($groupCol, 'DAY:') === 0) {
                $isDate = true;
                sort($grouping[$i]);
                $fullList = array();
                $dt = new \DateTime($grouping[$i][0]);
                $dtEnd = new \DateTime($grouping[$i][count($grouping[$i])  - 1]);
                if ($dt && $dtEnd) {
                    $interval = new \DateInterval('P1D');
                    while ($dt->getTimestamp() <= $dtEnd->getTimestamp()) {
                        $fullList[] = $dt->format('Y-m-d');
                        $dt->add($interval);
                    }
                    $grouping[$i] = $fullList;
                }
            } else if (strpos($groupCol, 'YEAR:') === 0) {
                $isDate = true;
                sort($grouping[$i]);
                $fullList = array();
                $dt = new \DateTime($grouping[$i][0] . '-01-01');
                $dtEnd = new \DateTime($grouping[$i][count($grouping[$i]) - 1] . '-01-01');
                if ($dt && $dtEnd) {
                    $interval = new \DateInterval('P1Y');
                    while ($dt->getTimestamp() <= $dtEnd->getTimestamp()) {
                        $fullList[] = $dt->format('Y');
                        $dt->add($interval);
                    }
                    $grouping[$i] = $fullList;
                }
            }

            if ($isDate) {
                if (in_array('DESC:' . $groupCol, $data->orderBy)) {
                    rsort($grouping[$i]);
                }

                $filterArray = [];
                if ($where) {
                	$filterArray = $filterArray + $where;
                }
                if (!empty($data->filtersWhere)) {
                	$arr = [];
                	foreach ($data->filtersWhere as $item) {
                		$arr[] = get_object_vars($item);
                	}
                	$filterArray = $filterArray + $arr;
                }

                if ($filterArray) {
                	if (strpos($groupCol, 'MONTH:') === 0) {
                		$fillToYearStart = false;
                		foreach ($filterArray as $item) {
                			if (empty($item['type']) || empty($item['attribute'])) continue;
                			if ($item['type'] === 'currentYear' || $item['type'] === 'lastYear') {
                				if ($item['attribute'] === substr($groupCol, 6)) {
	                				$fillToYearStart = true;
	                				break;
                				}
                			}
                		}
                		if ($fillToYearStart) {
                			if (count($grouping[$i])) {
                				$first = $grouping[$i][0];
                				list($year, $month) = explode('-', $first);
                				if (intval($month) > 1) {
                					for ($m = intval($month) - 1; $m >= 1; $m--) {
                						$newDate = $year . '-' . str_pad(strval($m), 2, '0', \STR_PAD_LEFT);
                						array_unshift($grouping[$i], $newDate);
                					}
                				}
                			}
                		}
                	}
                }

            }
        }

        if (count($params['groupBy']) === 1) {
        	$groupNumber = 0;
            $groupCol = $params['groupBy'][$groupNumber];
            if (
            	strpos($groupCol, 'MONTH:') === 0 ||
            	strpos($groupCol, 'YEAR:') === 0 ||
            	strpos($groupCol, 'DAY:') === 0
            ) {
            	foreach ($grouping[$groupNumber] as $groupValue) {
            		$isMet = false;
	                foreach ($rows as $row) {
	                	if ($groupValue === $row[$groupCol]) {
	                		$isMet = true;
	                		break;
	                	}
	                }
	                if ($isMet) continue;
	                $newRow = [];
	                $newRow[$groupCol] = $groupValue;
	                foreach ($data->columns as $column) {
	                	$newRow[$column] = 0;
	                }
	                $rows[] = $newRow;
            	}
            }
        } else {
            $groupCol1 = $params['groupBy'][0];
            $groupCol2 = $params['groupBy'][1];
            if (
            	strpos($groupCol1, 'MONTH:') === 0 ||
            	strpos($groupCol1, 'YEAR:') === 0 ||
            	strpos($groupCol1, 'DAY:') === 0 ||
            	strpos($groupCol2, 'MONTH:') === 0 ||
            	strpos($groupCol2, 'YEAR:') === 0 ||
            	strpos($groupCol2, 'DAY:') === 0
            ) {
            	$skipFilling = false;
            	if (strpos($groupCol1, 'DAY:') === 0 || strpos($groupCol2, 'DAY:') === 0) {
            		$skipFilling = true;
            		foreach ($data->columns as $column) {
            			if (strpos($column, 'AVG:') === 0) {
            				$skipFilling = false;
            			}
            		}
            	}
            	if (!$skipFilling) {
	            	foreach ($grouping[0] as $groupValue1) {
	            		foreach ($grouping[1] as $groupValue2) {
		            		$isMet = false;
			                foreach ($rows as $row) {
			                	if ($groupValue1 === $row[$groupCol1] && $groupValue2 === $row[$groupCol2]) {
			                		$isMet = true;
			                		break;
			                	}
			                }
			                if ($isMet) continue;
			                $newRow = [];
			                $newRow[$groupCol1] = $groupValue1;
			                $newRow[$groupCol2] = $groupValue2;
			                foreach ($data->columns as $column) {
			                	$newRow[$column] = 0;
			                }
			                $rows[] = $newRow;
			            }
	            	}
            	}
            }
        }

        $reportData = $this->buildGrid($rows, $params, $data->columns, $sums);

        foreach ($linkColumns as $column) {
            $groupNameMap[$column] = array();
            foreach ($rows as $row) {
                if (array_key_exists($column . 'Id', $row) && array_key_exists($column . 'Name', $row)) {
                    $groupNameMap[$column][$row[$column . 'Id']] = $row[$column . 'Name'];
                }
            }
        }

        $columnNameMap = array();
        foreach ($data->columns as $item) {
            if ($item == 'COUNT:id') {
                $columnNameMap[$item] = $this->getLanguage()->translate('COUNT', 'functions', 'Report');
                continue;
            }

            if (strpos($item, ':') !== false) {
                $func = substr($item, 0, strpos($item, ':'));
                $field = substr($item, strpos($item, ':') + 1);

                if (strpos($field, '.') !== false) {
                    list ($rel, $field) = explode('.', $field);
                    $foreignEntity = $this->getMetadata()->get('entityDefs.' . $entityType . '.links.' . $rel . '.entity');
                    if (empty($foreignEntity)) {
                        continue;
                    }

                    $entityTypeLocal = $foreignEntity;
                } else {
                    $entityTypeLocal = $entityType;
                }

                $suffix = '';
                if ($this->getMetadata()->get('entityDefs.' . $entityTypeLocal . '.fields.' . $field. '.type') == 'currencyConverted') {
                    $field = str_replace('Converted', '', $field);
                    $suffix = ' (' . $this->getConfig()->get('baseCurrency') . ')';
                }
                $fieldTranslated = $this->getLanguage()->translate($field, 'fields', $entityTypeLocal);

                $columnNameMap[$item] = $this->getLanguage()->translate($func, 'functions', 'Report') . ': ' . $fieldTranslated . $suffix;
            }
        }

        $result = array(
            'type' => 'Grid',
            'groupBy' => $data->groupBy,
            'columns' => $data->columns,
            'sums' => $sums,
            'groupNameMap' => $groupNameMap,
            'columnNameMap' => $columnNameMap,
            'depth' => count($data->groupBy),
            'grouping' => $grouping,
            'reportData' => $reportData,
            'entityType' => $entityType,
            'success' => !empty($data->success) ? $data->success : null,
            'chartColors' => $data->chartColors,
            'chartColor' => $data->chartColor
        );

        return $result;
    }

    protected function mergeGroupByColumns(&$rowList, &$groupByList, $key, $columnList)
    {
        foreach ($rowList as &$row) {
            $arr = [];
            foreach ($columnList as $column) {
                $value = $row[$column];
                if (empty($value)) {
                    $value = '';
                }
                $arr[] = $value;
            }
            $row[$key] = implode(':,:', $arr);
            foreach ($columnList as $column) {
                unset($row[$column]);
            }
        }

        foreach ($columnList as $j => $column) {
            foreach ($groupByList as $i => $groupByItem) {
                if ($groupByItem === $column) {
                    if ($j === 0) {
                        $groupByList[$i] = $key;
                    } else {
                        unset($groupByList[$i]);
                    }
                }
            }
        }

        $groupByList = array_values($groupByList);
    }

    protected function buildGrid($rows, $params, $columns, &$sums, $groups = array(), $number = 0)
    {
        $k = count($groups);

        $data = array();

        if ($k <= count($params['groupBy']) - 1) {

            $groupColumn = $params['groupBy'][$k];

            $keys = array();
            foreach ($rows as $row) {
                foreach ($groups as $i => $g) {
                    if ($row[$params['groupBy'][$i]] !== $g) {
                        continue 2;
                    }
                }

                $key = $row[$groupColumn];
                if (!in_array($key, $keys)) {
                    $keys[] = $key;
                }
            }

            foreach ($keys as $number => $key) {
                $gr = $groups;
                $gr[] = $key;
                $data[$key] = $this->buildGrid($rows, $params, $columns, $sums, $gr, $number + 1);
            }
        } else {
            $s = &$sums;

            for ($i = 0; $i < count($groups) - 1; $i++) {
                $group = $groups[$i];
                if (!array_key_exists($group, $s)) {
                    $s[$group] = array();
                }
                $s = &$s[$group];
            }

            foreach ($rows as $j => $row) {
                foreach ($groups as $i => $g) {
                    if ($row[$params['groupBy'][$i]] != $g) {
                        continue 2;
                    }
                }

                foreach ($params['select'] as $c) {
                    if (in_array($c, $columns)) {
                        if (empty($s[$c])) {
                            $s[$c] = 0;
                            if (strpos($c, 'MIN:') === 0) {
                                $s[$c] = null;
                            }
                        }

                        if (strpos($c, 'COUNT:') === 0) {
                            $value = intval($row[$c]);
                        } else {
                            $value = floatval($row[$c]);
                        }
                        if (strpos($c, 'MIN:') === 0) {
                            if (is_null($s[$c]) || $s[$c] >= $value) {
                                $s[$c] = $value;
                            }
                        } else if (strpos($c, 'MAX:') === 0) {
                            if ($s[$c] < $value) {
                                $s[$c] = $value;
                            }
                        } else if (strpos($c, 'AVG:') === 0) {
                            $s[$c] = $s[$c] + ($value - $s[$c]) / floatval($number);

                        } else {
                            $s[$c] = $s[$c] + $value;
                        }
                        $data[$c] = $value;
                    }
                }
            }
        }
        return $data;
    }

    protected function getGridReportResultForExport($id, $where, $column = null)
    {
        $data = $this->run($id, $where);

        $depth = $data['depth'];

        $reportData = $data['reportData'];

        $result = array();
        if ($depth == 2) {
            $groupName1 = $data['groupBy'][0];
            $groupName2 = $data['groupBy'][1];

            $row = array();
            $row[] = '';
            foreach ($data['grouping'][1] as $gr2) {
                $label = $gr2;
                if (empty($label)) {
                    $label = $this->getLanguage()->translate('-Empty-', 'labels', 'Report');
                } else if (!empty($data['groupNameMap'][$groupName2][$gr2])) {
                    $label = $data['groupNameMap'][$groupName2][$gr2];
                }
                $row[] = $label;
            }
            $row[] = $this->getLanguage()->translate('Total', 'labels', 'Report');

            $result[] = $row;

            foreach ($data['grouping'][0] as $gr1) {
                $row = array();
                $label = $gr1;
                if (empty($label)) {
                    $label = $this->getLanguage()->translate('-Empty-', 'labels', 'Report');
                } else if (!empty($data['groupNameMap'][$groupName1][$gr1])) {
                    $label = $data['groupNameMap'][$groupName1][$gr1];
                }
                $row[] = $label;
                foreach ($data['grouping'][1] as $gr2) {
                    $value = 0;
                    if (!empty($reportData[$gr1]) && !empty($reportData[$gr1][$gr2])) {
                        if (!empty($reportData[$gr1][$gr2][$column])) {
                            $value = $reportData[$gr1][$gr2][$column];
                        }
                    }
                    $row[] = $value;
                }
                $sum = 0;

                if (!empty($data['sums'][$gr1])) {
                    if (!empty($data['sums'][$gr1][$column])) {
                        $sum = $data['sums'][$gr1][$column];
                    }
                }
                $row[] = $sum;
                $result[] = $row;
            }

            $out = array();
            foreach ($result as $i => $row) {
                foreach ($row as $j => $value) {
                    $out[$j][$i] = $value;
                }
            }
            $result = $out;
        } else if ($depth == 1) {
            $groupName = $data['groupBy'][0];

            $row = array();
            $row[] = '';
            foreach ($data['columns'] as $column) {
                $label = $column;
                if (!empty($data['columnNameMap'][$column])) {
                    $label = $data['columnNameMap'][$column];
                }
                $row[] = $label;
            }
            $result[] = $row;

            foreach ($data['grouping'][0] as $gr) {
                $row = array();
                $label = $gr;
                if (empty($label)) {
                    $label = $this->getLanguage()->translate('-Empty-', 'labels', 'Report');
                } else if (!empty($data['groupNameMap'][$groupName][$gr])) {
                    $label = $data['groupNameMap'][$groupName][$gr];
                }
                $row[] = $label;
                foreach ($data['columns'] as $column) {
                    $value = 0;
                    if (!empty($reportData[$gr])) {
                        if (!empty($reportData[$gr][$column])) {
                            $value = $reportData[$gr][$column];
                        }
                    }
                    $row[] = $value;
                }
                $result[] = $row;
            }
            $row = array();
            $row[] = $this->getLanguage()->translate('Total', 'labels', 'Report');
            foreach ($data['columns'] as $column) {
                $sum = 0;
                if (!empty($data['sums'][$column])) {
                    $sum = $data['sums'][$column];
                }
                $row[] = $sum;
            }
            $result[] = $row;
        }

        return $result;
    }

    public function getGridReportCsv($id, $where, $column = null)
    {
    	$result = $this->getGridReportResultForExport($id, $where, $column);

        $delimiter = $this->getConfig()->get('exportDelimiter', ';');

        $fp = fopen('php://temp', 'w');
        foreach ($result as $row) {
            fputcsv($fp, $row, $delimiter);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

    	return $csv;
    }

    public function getDataFromColumnName($entityType, $column)
    {
        $field = $column;
        $link = null;
        $function = null;
        if (strpos($field, ':') !== false) {
            list($function, $field) = explode(':', $field);
        }

        if (strpos($field, '.') !== false) {
            list($link, $field) = explode('.', $field);
            $scope = $this->getMetadata()->get(['entityDefs', $entityType, 'links', $link, 'entity']);
        } else {
            $scope = $entityType;
        }

        return (object) array(
            'function' => $function,
            'field' => $field,
            'entityType' => $scope,
            'link' => $link
        );
    }

    public function getGridReportXlsx($id, $where)
    {

        $report = $this->getEntityManager()->getEntity('Report', $id);

        $entityType = $report->get('entityType');

        $groupCount = count($report->get('groupBy'));

        $columnList = $report->get('columns');

        $result = [];
        if ($groupCount === 2) {
            foreach ($columnList as $column) {
                $r = $this->getGridReportResultForExport($id, $where, $column);
                $result[] = $r;
            }
        } else {
            $result[] = $this->getGridReportResultForExport($id, $where);
        }

        $columnTypes = array();

        foreach ($columnList as $item) {
            $columnData = $this->getDataFromColumnName($entityType, $item);
            $type = $this->getMetadata()->get(['entityDefs', $columnData->entityType, 'fields', $columnData->field, 'type']);
            if ($columnData->function === 'COUNT') {
                $type = 'int';
            }
            $columnTypes[$item] = $type;
        }

        $columnLabels = array();
        foreach ($columnList as $column) {
            $columnLabels[$column] = $this->translateColumnName($entityType, $column);
        }

        $exportParams = array(
            'exportName' => $report->get('name'),
            'columnList' => $columnList,
            'columnTypes' => $columnTypes,
            'chartType' => $report->get('chartType'),
            'groupCount' => $groupCount,
            'groupByList' => $report->get('groupBy'),
            'columnLabels' => $columnLabels,
            'is2d' => $groupCount === 2
        );

        $group = $report->get('groupBy')[count($report->get('groupBy')) - 1];
        $exportParams['groupLabel'] = $this->translateColumnName($entityType, $group);

        $exportClassName = '\\Core\\Modules\\Advanced\\Core\\Report\\ExportXlsx';
        $exportObj = $this->getInjection('injectableFactory')->createByClassName($exportClassName);

        return $exportObj->process($entityType, $exportParams, $result);
    }

    protected function translateColumnName($entityType, $item)
    {
        $field = $item;
        $function = null;
        if (strpos($item, ':') !== false) {
            list($function, $field) = explode(':', $item);
        }
        if (strpos($field, '.') !== false) {
            list($link, $field) = explode('.', $field);

            $scope = $this->getMetadata()->get(['entityDefs', $entityType, 'links', $link, 'entity']);
            $groupLabel = $this->getInjection('language')->translate($link, 'links', $entityType);
            $groupLabel .= '.' . $this->getInjection('language')->translate($field, 'fields', $scope);
        } else {
            $groupLabel = $this->getInjection('language')->translate($field, 'fields', $entityType);
        }

        if ($function) {
            $functionLabel = $this->getInjection('language')->translate($function, 'functions', 'Report');
            if ($function === 'COUNT' && $field === 'id') {
                return $functionLabel;
            }
            $groupLabel = $functionLabel . ': ' . $groupLabel;
        }
        return $groupLabel;
    }

    public function populateTargetList($id, $targetListId)
    {
        $report = $this->getEntityManager()->getEntity('Report', $id);
        if (!$report) {
            throw new NotFound();
        }
        if (!$this->getAcl()->check($report, 'read')) {
            throw new Forbidden();
        }

        $targetList = $this->getEntityManager()->getEntity('TargetList', $targetListId);
        if (!$targetList) {
            throw new NotFound();
        }
        if (!$this->getAcl()->check($targetList, 'edit')) {
            throw new Forbidden();
        }

        if ($report->get('type') != 'List') {
            throw new Error();
        }

        $entityType = $report->get('entityType');

        switch ($entityType) {
            case 'Contact':
                $link = 'contacts';
                break;
            case 'Lead':
                $link = 'leads';
                break;
            case 'User':
                $link = 'users';
                break;
            case 'Account':
                $link = 'accounts';
                break;
            default:
                throw new Error();
        }

        $data = $report->get('data');
        if (empty($data)) {
            $data = new \StdClass();
        }
        $data->orderBy = $report->get('orderBy');
        $data->columns = $report->get('columns');

        if ($report->get('filtersData') && !$report->get('filtersDataList')) {
            $data->filtersWhere = $this->convertFiltersData($report->get('filtersData'));
        } else {
            $data->filtersWhere = $this->convertFiltersDataList($report->get('filtersDataList'));
        }

        $rawParams = array();
        $selectManager = $this->getSelectManagerFactory()->create($entityType);
        $params = $selectManager->getSelectParams($rawParams);

        if (!empty($data->filtersWhere)) {
            $filtersWhere = json_decode(json_encode($data->filtersWhere), true);
            $this->handleFilters($filtersWhere, $entityType, $params);
        }

        return $this->getEntityManager()->getRepository('TargetList')->massRelate($targetList, $link, $params);
    }

    public function syncTargetListWithReports(Entity $targetList)
    {
        if (!$this->getAcl()->check($targetList, 'edit')) {
            throw new Forbidden();
        }

        $targetListService = $this->getServiceFactory()->create('TargetList');

        if ($targetList->get('syncWithReportsUnlink')) {
            $targetListService->unlinkAll($targetList->id, 'contacts');
            $targetListService->unlinkAll($targetList->id, 'leads');
            $targetListService->unlinkAll($targetList->id, 'accounts');
            $targetListService->unlinkAll($targetList->id, 'users');
        }
        $reportList = $this->getEntityManager()->getRepository('TargetList')->findRelated($targetList, 'syncWithReports');
        foreach ($reportList as $report) {
            $this->populateTargetList($report->id, $targetList->id);
        }
        return true;
    }

    public function exportList($id, $where = null, array $params = null)
    {
        $additionalParams = array();

        if (!array_key_exists('fieldList', $params)) {
            $additionalParams['fullSelect'] = true;
        } else {
            $additionalParams['customColumnList'] = $params['fieldList'];
            foreach ($additionalParams['customColumnList'] as $i => $item) {
                if (strpos($item, '_') !== false) {
                    $additionalParams['customColumnList'][$i] = str_replace('_', '.', $item);
                }
            }
        }

        if (!empty($params['ids']) && is_array($params['ids'])) {
            if (is_null($where)) {
                $where = [];
            }
            $where[] = array(
                'type' => 'equals',
                'attribute' => 'id',
                'value' => $params['ids']
            );
        }

        $reportParams = array(
            'sortBy' => $params['sortBy'],
            'asc' => $params['asc'],
            'groupValue' => $params['groupValue']
        );

        $resultData = $this->run($id, $where, $reportParams, $additionalParams);

        $report = $this->getEntity($id);

        $entityType = $report->get('entityType');

        if (!array_key_exists('collection', $resultData)) {
            throw new Error();
        }

        $collection = $resultData['collection'];

        $service = $this->getRecordService($entityType);

        $exportParams = array();
        if (array_key_exists('attributeList', $params)) {
            $exportParams['attributeList'] = $params['attributeList'];
        }
        if (array_key_exists('fieldList', $params)) {
            $exportParams['fieldList'] = $params['fieldList'];
        }
        if (array_key_exists('format', $params)) {
            $exportParams['format'] = $params['format'];
        }

        $exportParams['exportName'] = $report->get('name');

        $exportParams['fileName'] = $report->get('name') . ' ' . date('Y-m-d');

        if (method_exists($service, 'exportCollection')) {
            return $service->exportCollection($exportParams, $collection);
        }

        // code bellow is for backward compatibility

        $arr = array();

        $dataList = $collection->toArray();

        $attributeListToSkip = array(
            'deleted',
        );

        $attributeList = null;
        if (array_key_exists('attributeList', $params)) {
            $attributeList = [];
            $entity = $this->getEntityManager()->getEntity($entityType);
            foreach ($params['attributeList'] as $attribute) {
                if (in_array($attribute, $attributeListToSkip)) {
                    continue;
                }

                if (method_exists($service, 'checkAttributeIsAllowedForExport')) {
                    if ($service->checkAttributeIsAllowedForExport($entity, $attribute)) {
                        $attributeList[] = $attribute;
                    }
                } else {
                    if (empty($defs['notStorable'])) {
                        $attributeList[] = $attribute;
                    } else {
                        if (in_array($defs['type'], ['email', 'phone'])) {
                            $attributeList[] = $attribute;
                        }
                    }
                }
            }
        }

        foreach ($collection as $entity) {
            if (is_null($attributeList)) {
                $attributeList = [];
                foreach ($entity->getAttributes() as $attribute => $defs) {
                    if (in_array($attribute, $attributeListToSkip)) {
                        continue;
                    }

                    if (method_exists($service, 'checkAttributeIsAllowedForExport')) {
                        if ($service->checkAttributeIsAllowedForExport($entity, $attribute)) {
                            $attributeList[] = $attribute;
                        }
                    } else {
                        if (empty($defs['notStorable'])) {
                            $attributeList[] = $attribute;
                        } else {
                            if (in_array($defs['type'], ['email', 'phone'])) {
                                $attributeList[] = $attribute;
                            }
                        }
                    }
                }
            }

            $row = array();
            foreach ($attributeList as $attribute) {
                if (method_exists($service, 'getAttributeFromEntityForExport')) {
                    $value = $service->getAttributeFromEntityForExport($entity, $attribute);
                } else {
                    $value = $service->getFieldFromEntityForExport($entity, $attribute);
                }
                $row[$attribute] = $value;
            }
            $arr[] = $row;
        }

        $delimiter = $this->getPreferences()->get('exportDelimiter');
        if (empty($delimiter)) {
            $delimiter = $this->getConfig()->get('exportDelimiter', ';');
        }

        $fp = fopen('php://temp', 'w');
        fputcsv($fp, array_keys($arr[0]), $delimiter);
        foreach ($arr as $row) {
            fputcsv($fp, $row, $delimiter);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        $fileName = "Export_{$entityType}.csv";

        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set('name', $fileName);
        $attachment->set('role', 'Export File');
        $attachment->set('type', 'text/csv');

        $this->getEntityManager()->saveEntity($attachment);

        if (!empty($attachment->id)) {
            $this->getInjection('fileManager')->putContents('data/upload/' . $attachment->id, $csv);
            return $attachment->id;
        }
        throw new Error();
    }
}

