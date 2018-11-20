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

namespace Core\Modules\Crm\Jobs;

use \Core\Core\Exceptions;

class ControlKnowledgeBaseArticleStatus extends \Core\Core\Jobs\Base
{
    public function run()
    {
        $list = $this->getEntityManager()->getRepository('KnowledgeBaseArticle')->where(array(
            'expirationDate<=' => date('Y-m-d'),
            'status' => 'Published'
        ))->find();

        foreach ($list as $e) {
            $e->set('status', 'Archived');
            $this->getEntityManager()->saveEntity($e);
        }

        return true;
    }
}

