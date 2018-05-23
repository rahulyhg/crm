<?php


namespace Core\Modules\Crm\Controllers;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;

class Opportunity extends \Core\Core\Controllers\Record
{
    public function actionReportByLeadSource($params, $data, $request)
    {
        $level = $this->getAcl()->getLevel('Opportunity', 'read');
        if (!$level || $level == 'own' || $level == 'no') {
            throw new Forbidden();
        }

        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');

        return $this->getService('Opportunity')->reportByLeadSource($dateFrom, $dateTo);
    }

    public function actionReportByStage($params, $data, $request)
    {
        $level = $this->getAcl()->getLevel('Opportunity', 'read');
        if (!$level || $level == 'own' || $level == 'no') {
            throw new Forbidden();
        }

        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');

        return $this->getService('Opportunity')->reportByStage($dateFrom, $dateTo);
    }

    public function actionReportSalesByMonth($params, $data, $request)
    {
        $level = $this->getAcl()->getLevel('Opportunity', 'read');
        if (!$level || $level == 'own' || $level == 'no') {
            throw new Forbidden();
        }

        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');

        return $this->getService('Opportunity')->reportSalesByMonth($dateFrom, $dateTo);
    }

    public function actionReportSalesPipeline($params, $data, $request)
    {
        $level = $this->getAcl()->getLevel('Opportunity', 'read');
        if (!$level || $level == 'own' || $level == 'no') {
            throw new Forbidden();
        }

        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');

        return $this->getService('Opportunity')->reportSalesPipeline($dateFrom, $dateTo);
    }
}

