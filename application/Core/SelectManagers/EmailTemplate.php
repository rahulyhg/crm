<?php


namespace Core\SelectManagers;

class EmailTemplate extends \Core\Core\SelectManagers\Base
{

    protected function textFilter($textFilter, &$result)
    {
        $d = array();

        $d['name*'] = '' . $textFilter . '%';
        $d['subject*'] = '%' . $textFilter . '%';

        if (strlen($textFilter) >= self::MIN_LENGTH_FOR_CONTENT_SEARCH) {
            $d['bodyPlain*'] = '%' . $textFilter . '%';
            $d['body*'] = '%' . $textFilter . '%';
        }

        $result['whereClause'][] = array(
            'OR' => $d
        );
    }

    protected function filterActual(&$result)
    {

        $result['whereClause'][] = array(
            'oneOff!=' => true
        );
    }

}

