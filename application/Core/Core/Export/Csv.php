<?php


namespace Core\Core\Export;

use \Core\Core\Exceptions\Error;

class Csv extends \Core\Core\Injectable
{
    protected $dependencyList = [
        'config',
        'preferences'
    ];

    public function process($entityType, $params, $dataList)
    {
        if (!is_array($params['attributeList'])) {
            throw new Error();
        }

        $attributeList = $params['attributeList'];

        $delimiter = $this->getInjection('preferences')->get('exportDelimiter');
        if (empty($delimiter)) {
            $delimiter = $this->getInjection('config')->get('exportDelimiter', ';');
        }

        $fp = fopen('php://temp', 'w');
        fputcsv($fp, $attributeList, $delimiter);
        foreach ($dataList as $row) {
            fputcsv($fp, $row, $delimiter);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }
}