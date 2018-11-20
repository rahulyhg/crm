<?php
/************************************************************************
 * This file is part of CRM.
 *
 * CRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * CRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * CRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/

namespace Core\Modules\Advanced\EntryPoints;

use \Core\Core\Utils\Util;

use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\BadRequest;
use \Core\Core\Exceptions\Error;

class ReportAsCsv extends \Core\Core\EntryPoints\Base
{
    public static $authRequired = true;

    public function run()
    {
        if (empty($_GET['id'])) {
            throw new BadRequest();
        }

        $id = $_GET['id'];


        $where = null;
        if (!empty($_GET['where'])) {
            $where = $_GET['where'];
        }

        $column = null;
        if (!empty($_GET['column'])) {
            $column = $_GET['column'];
        }

        $service = $this->getServiceFactory()->create('Report');

        if (!empty($where)) {
            $where = json_decode($where, true);
        }

        $contents = $service->getGridReportCsv($id, $where, $column);

        $report = $this->getEntityManager()->getEntity('Report', $id);

        $name = $report->get('name');

        $name = preg_replace("/([^\w\s\d\-_~,;:\[\]\(\).])/u", '_', $name) . ' ' . date('Y-m-d');

        $mimeType = $this->getMetadata()->get(['app', 'export', 'formatDefs', 'csv', 'mimeType']);
        $fileExtension = $this->getMetadata()->get(['app', 'export', 'formatDefs', 'csv', 'fileExtension']);

        $fileName = $name . '.' . $fileExtension;

        ob_clean();
        header("Content-type:{$mimeType}");
        header("Content-Disposition:attachment;filename=\"{$fileName}\"");
        echo $contents;
    }
}

