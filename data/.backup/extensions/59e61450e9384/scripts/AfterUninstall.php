<?php
/************************************************************************
 * This file is part of CoreCRM.
 *
 * CoreCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * CoreCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * CoreCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CoreCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/

class AfterUninstall
{
    protected $container;

    public function run($container)
    {
        $this->container = $container;

        $entityManager = $this->container->get('entityManager');

        if ($job = $entityManager->getRepository('ScheduledJob')->where(array('job' => 'SynchronizeEventsWithGoogleCalendar'))->findOne()) {
            $entityManager->removeEntity($job);
        }
        if ($job = $entityManager->getRepository('ScheduledJob')->where(array('job' => 'MailChimpSyncData'))->findOne()) {
            $entityManager->removeEntity($job);
        }
        if ($job = $entityManager->getRepository('ScheduledJob')->where(array('job' => 'ReportTargetListSync'))->findOne()) {
            $entityManager->removeEntity($job);
        }
        if ($job = $entityManager->getRepository('ScheduledJob')->where(array('job' => 'ScheduleReportSending'))->findOne()) {
            $entityManager->removeEntity($job);
        }
        if ($job = $entityManager->getRepository('ScheduledJob')->where(array('job' => 'RunScheduledWorkflows'))->findOne()) {
            $entityManager->removeEntity($job);
        }

        $config = $this->container->get('config');
        $iframeUrl = preg_replace('/.advanced-pack=[^&]+/i', '', $config->get('adminPanelIframeUrl'));
        $config->set('adminPanelIframeUrl', $iframeUrl);
        $config->save();
    }
}
