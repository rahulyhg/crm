<?php
/************************************************************************
 * This file is part of Samex CRM.
 *
 * Samex CRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * Samex CRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Samex CRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Samex CRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/

class AfterInstall
{
    protected $container;

    public function run($container)
    {
        $this->container = $container;

        $entityManager = $this->container->get('entityManager');

        $pdo = $entityManager->getPDO();

        if (!$entityManager->getRepository('ScheduledJob')->where(array('job' => 'SynchronizeEventsWithGoogleCalendar'))->findOne()) {
            $job = $entityManager->getEntity('ScheduledJob');
            $job->set(array(
               'name' => 'Google Calendar Sync',
               'job' => 'SynchronizeEventsWithGoogleCalendar',
               'status' => 'Active',
               'scheduling' => '*/10 * * * *',
            ));
            $entityManager->saveEntity($job);
        }

        if (!$entityManager->getRepository('ScheduledJob')->where(array('job' => 'MailChimpSyncData'))->findOne()) {
            $job = $entityManager->getEntity('ScheduledJob');
            $job->set(array(
               'name' => 'MailChimp Sync',
               'job' => 'MailChimpSyncData',
               'status' => 'Active',
               'scheduling' => '0 3 * * *',
            ));
            $entityManager->saveEntity($job);
        }

        if (!$entityManager->getRepository('ScheduledJob')->where(array('job' => 'RunMailChimpQueueItems'))->findOne()) {
            $job = $entityManager->getEntity('ScheduledJob');
            $job->set(array(
               'name' => 'Run MailChimp Queue Items',
               'job' => 'RunMailChimpQueueItems',
               'status' => 'Active',
               'scheduling' => '*/2 * * * *',
            ));
            $entityManager->saveEntity($job);
        }

        if (!$entityManager->getRepository('ScheduledJob')->where(array('job' => 'ReportTargetListSync'))->findOne()) {
            $job = $entityManager->getEntity('ScheduledJob');
            $job->set(array(
               'name' => 'Sync Target Lists with Reports',
               'job' => 'ReportTargetListSync',
               'status' => 'Active',
               'scheduling' => '0 2 * * *',
            ));
            $entityManager->saveEntity($job);
        }

        if (!$entityManager->getRepository('ScheduledJob')->where(array('job' => 'ScheduleReportSending'))->findOne()) {
            $job = $entityManager->getEntity('ScheduledJob');
            $job->set(array(
               'name' => 'Schedule Report Sending',
               'job' => 'ScheduleReportSending',
               'status' => 'Active',
               'scheduling' => '0 * * * *',
            ));
            $entityManager->saveEntity($job);
        }

        if (!$entityManager->getRepository('ScheduledJob')->where(array('job' => 'RunScheduledWorkflows'))->findOne()) {
            $job = $entityManager->getEntity('ScheduledJob');
            $job->set(array(
               'name' => 'Run Scheduled Workflows',
               'job' => 'RunScheduledWorkflows',
               'status' => 'Active',
               'scheduling' => '*/10 * * * *',
            ));
            $entityManager->saveEntity($job);
        }

        $sql = "SELECT * FROM report WHERE id = '001'";
        $sth = $pdo->prepare($sql);
        $sth->execute();
        if (!$sth->fetch()) {
            $sql = "
                INSERT INTO `report` (`id`, `name`, `entity_type`, `type`, `data`, `columns`, `group_by`, `order_by`, `filters`, `runtime_filters`, `filters_data`, `description`, `chart_type`, `depth`, `is_internal`, `internal_class_name`, `created_at`, `modified_at`, `deleted`, `assigned_user_id`, `modified_by_id`, `created_by_id`) VALUES
                ('010', 'Leads by Source', 'Lead', 'Grid', NULL, '[\"COUNT:id\"]', '[\"source\"]', '[\"LIST:source\"]', '[\"status\"]', '[]', '{\"status\":{\"type\":\"in\",\"value\":[\"New\",\"Assigned\",\"In Process\"],\"field\":\"status\"}}', '', 'Pie', NULL, 0, NULL, NULL, '2014-11-05 14:35:20', 0, '1', '1', '1'),
                ('009', 'Monthly Revenue', 'Opportunity', 'Grid', '{\"success\":\"Closed Won\"}', '[\"SUM:amountConverted\"]', '[\"MONTH:closeDate\"]', '[\"ASC:MONTH:closeDate\"]', '[\"stage\"]', '[\"closeDate\"]', '{\"stage\":{\"type\":\"in\",\"value\":[\"Closed Won\"],\"field\":\"stage\"}}', '', 'Line', NULL, 0, NULL, NULL, '2014-11-05 14:33:30', 0, '1', '1', '1'),
                ('008', 'Leads by Status', 'Lead', 'Grid', '{\"success\":\"Converted\"}', '[\"COUNT:id\"]', '[\"status\"]', '[\"LIST:status\"]', '[\"status\"]', '[]', '{\"status\":{\"type\":\"in\",\"value\":[\"New\",\"Assigned\",\"In Process\"],\"field\":\"status\"}}', '', 'BarHorizontal', NULL, 0, NULL, NULL, '2014-11-05 15:05:03', 0, '1', '1', '1'),
                ('007', 'Monthly Revenue by User', 'Opportunity', 'Grid', NULL, '[\"SUM:amountConverted\"]', '[\"MONTH:closeDate\",\"assignedUser\"]', '[\"ASC:MONTH:closeDate\",\"assignedUser\"]', '[\"stage\"]', '[\"closeDate\"]', '{\"stage\":{\"type\":\"in\",\"value\":[\"Closed Won\"],\"field\":\"stage\"}}', '', 'Line', NULL, 0, NULL, NULL, '2014-11-05 14:30:15', 0, '1', '1', '1'),
                ('006', 'Opportunities by User', 'Opportunity', 'Grid', NULL, '[\"COUNT:id\",\"SUM:amountWeightedConverted\",\"SUM:amountConverted\"]', '[\"assignedUser\"]', '[\"ASC:assignedUser\"]', '[\"stage\"]', '[]', '{\"stage\":{\"type\":\"in\",\"value\":[\"Prospecting\",\"Qualification\",\"Needs Analysis\",\"Value Proposition\",\"Id. Decision Makers\",\"Perception Analysis\",\"Proposal\\/Price Quote\",\"Negotiation\\/Review\"],\"field\":\"stage\"}}', '', 'BarVertical', NULL, 0, NULL, NULL, '2014-11-05 14:49:23', 0, '1', '1', '1'),
                ('004', 'Opportunities by Lead Source and User', 'Opportunity', 'Grid', NULL, '[\"COUNT:id\",\"SUM:amountWeightedConverted\"]', '[\"assignedUser\",\"leadSource\"]', '[\"LIST:leadSource\",\"ASC:assignedUser\"]', '[\"stage\"]', '[]', '{\"stage\":false}', '', 'BarVertical', NULL, 0, NULL, NULL, '2014-12-26 14:57:29', 0, '1', '1', '1'),
                ('005', 'Leads by User', 'Lead', 'Grid', NULL, '[\"COUNT:id\"]', '[\"assignedUser\"]', '[\"ASC:assignedUser\"]', '[\"status\"]', '[]', '{\"status\":{\"type\":\"in\",\"value\":[\"New\",\"Assigned\",\"In Process\"],\"field\":\"status\"}}', '', 'BarVertical', NULL, 0, NULL, NULL, '2014-11-05 14:36:17', 0, '1', '1', '1'),
                ('003', 'Calls by Account and User', 'Call', 'Grid', NULL, '[\"COUNT:id\"]', '[\"account\",\"assignedUser\"]', '[]', '[\"status\"]', '[\"dateStart\"]', '{\"status\":{\"type\":\"in\",\"value\":[\"Held\"],\"field\":\"status\"}}', '', 'BarVertical', NULL, 0, NULL, '2014-10-30 09:34:44', '2014-11-05 15:07:42', 0, '1', '1', '1'),
                ('001', 'Leads by Last Activity', 'Lead', 'Grid', NULL, '[\"COUNT:id\"]', NULL, NULL, NULL, NULL, NULL, '', 'BarVertical', 2, 1, 'Advanced:LeadsByLastActivity', NULL, '2014-11-05 14:43:16', 0, '1', '1', '1'),
                ('002', 'Won Opportunities', 'Opportunity', 'List', NULL, '[\"name\",\"account\",\"closeDate\",\"amount\"]', NULL, NULL, '[]', '[\"closeDate\"]', '{}', '', NULL, NULL, 0, NULL, '2014-10-28 08:52:44', '2014-10-30 09:59:18', 0, '1', '1', '1');
            ";
            $pdo->query($sql);
        }

        try {
            $sql = "SELECT * FROM `template` WHERE id = '001'";
            $sth = $pdo->prepare($sql);
            $sth->execute();
            if (!$sth->fetch()) {
                $sql = <<<EOQ
INSERT INTO `template` (`id`, `name`, `deleted`, `body`, `header`, `footer`, `entity_type`, `left_margin`, `right_margin`, `top_margin`, `bottom_margin`, `print_footer`, `footer_position`, `created_at`, `modified_at`, `created_by_id`, `modified_by_id`) VALUES
('001', 'Invoice', 0, '<p>Currency: {{amountCurrency}}</p>\n\n <table border="0.5pt" cellpadding="2" class="table table-bordered">\n<tbody>\n <tr>\n  <th width="5%" align="left">#</th>\n  <th width="37%" align="left">Name</th>\n  <th width="8%" align="left">Qty</th>\n  <th width="16%" align="left">List Price</th>\n  <th width="16%" align="left">Unit Price</th>\n  <th width="16%">Amount</th>\n </tr>\n <!-- {{#each itemList}} --> <tr>\n  <td>{{order}}</td>\n  <td>{{name}}</td>\n  <td>{{quantity}}</td>\n  <td align="right">{{listPrice}}</td>\n  <td align="right">{{unitPrice}}</td>\n  <td align="right">{{amount}}</td>\n </tr>\n<!-- {{#if description}} -->\n<tr><td colspan="7">{{{description}}}</td></tr>\n<!-- {{/if}} -->\n<!-- {{/each}} --> <tr>\n   <td colspan="7"></td>\n </tr>\n <tr>\n   <td colspan="5" align="right">Pre-Discounted Amount</td>\n   <td align="right">{{preDiscountedAmount}}</td>\n </tr>\n <tr>\n   <td colspan="5" align="right">Discount Amount</td>\n   <td align="right">{{discountAmount}}</td>\n </tr>\n <tr>\n   <td colspan="5" align="right">Amount</td>\n   <td align="right">{{amount}}</td>\n </tr>\n <tr>\n   <td colspan="5" align="right">Tax Amount</td>\n   <td align="right">{{taxAmount}}</td>\n </tr>\n <tr>\n   <td colspan="5" align="right">Shipping Cost</td>\n   <td align="right">{{shippingCost}}</td>\n </tr>\n <tr>\n   <td colspan="5" align="right">Grand Total Amount</td>\n   <td align="right"><b>{{grandTotalAmount}}</b></td>\n </tr>\n</tbody>\n</table>\n<p><br></p>\n<p align="center">Thank you for your business.</p>', '<table class="table table-bordered" style="line-height: 1.36; background-color: rgb(255, 255, 255);"><tbody><tr><td width="50%"><p><span style="font-size: 18px;">Company Name</span></p><p><span style="font-size: 12px;">{{accountName}}</span><br><span style="font-size: 12px;">{{{billingAddressStreet}}}</span><br><span style="font-size: 12px;">{{billingAddressCity}}{{#if billingAddressState}},{{/if}} {{billingAddressState}} {{billingAddressPostalCode}}</span><br><span style="font-size: 12px;">{{billingAddressCountry}}<br></span><span style="font-size: 18px;"><br></span></p></td><td width="50%"><p style="text-align: right; "><span style="color: rgb(156, 156, 148); font-size: 18px; line-height: 24.4799995422363px;">Invoice</span></p><p></p><div style="text-align: right;"><span style="font-size: 12px; line-height: 24.4799995422363px; color: rgb(0, 0, 0);">Date: {{dateInvoiced}}</span></div><br><p></p></td></tr></tbody></table><p><span style="font-size: 18px; line-height: 32.6399993896484px;">{{name}}</span></p><p><span style="font-size: 18px; line-height: 32.6399993896484px;"><br></span></p>', '<div style="text-align: center;"><span style="font-size: 10px;">{pageNumber}</span></div>', 'Quote', 10, 10, 10, 25, 1, 15, '2015-07-21 09:40:55', '2016-03-28 09:11:02', '1', '1'),
('002', 'Quote', 0, '<p>Currency: {{amountCurrency}}</p>\n\n <table border="0.5pt" cellpadding="2" class="table table-bordered">\n<tbody>\n <tr>\n  <th width="5%" align="left">#</th>\n  <th width="37%" align="left">Name</th>\n  <th width="8%" align="left">Qty</th>\n  <th width="16%" align="left">List Price</th>\n  <th width="16%" align="left">Unit Price</th>\n  <th width="16%">Amount</th>\n </tr>\n <!-- {{#each itemList}} --> <tr>\n  <td>{{order}}</td>\n  <td>{{name}}</td>\n  <td>{{quantity}}</td>\n  <td align="right">{{listPrice}}</td>\n  <td align="right">{{unitPrice}}</td>\n  <td align="right">{{amount}}</td>\n </tr>\n<!-- {{#if description}} -->\n<tr><td colspan="7">{{{description}}}</td></tr>\n<!-- {{/if}} -->\n<!-- {{/each}} --> <tr>\n   <td colspan="7"></td>\n </tr>\n <tr>\n   <td colspan="5" align="right">Pre-Discounted Amount</td>\n   <td align="right">{{preDiscountedAmount}}</td>\n </tr>\n <tr>\n   <td colspan="5" align="right">Discount Amount</td>\n   <td align="right">{{discountAmount}}</td>\n </tr>\n <tr>\n   <td colspan="5" align="right">Amount</td>\n   <td align="right">{{amount}}</td>\n </tr>\n <tr>\n   <td colspan="5" align="right">Tax Amount</td>\n   <td align="right">{{taxAmount}}</td>\n </tr>\n <tr>\n   <td colspan="5" align="right">Shipping Cost</td>\n   <td align="right">{{shippingCost}}</td>\n </tr>\n <tr>\n   <td colspan="5" align="right">Grand Total Amount</td>\n   <td align="right"><b>{{grandTotalAmount}}</b></td>\n </tr>\n</tbody>\n</table>\n<p><br></p>\n<p align="center">Thank you for your business.</p>', '<table class="table table-bordered" style="line-height: 1.36; background-color: rgb(255, 255, 255);">\n<tbody>\n<tr>\n<td width="50%"><p><span style="font-size: 18px;">Company Name</span></p>\n<p><span style="font-size: 12px;">{{accountName}}</span><br><span style="font-size: 12px;">{{{billingAddressStreet}}}</span><br><span style="font-size: 12px;">{{billingAddressCity}}{{#if billingAddressState}},{{/if}} {{billingAddressState}} {{billingAddressPostalCode}}</span><br><span style="font-size: 12px;">{{billingAddressCountry}}<br></span><span style="font-size: 18px;"><br></span></p>\n</td>\n<td width="50%"><p style="text-align: right; "><span style="color: rgb(156, 156, 148); font-size: 18px; line-height: 24.4799995422363px;">Quote</span></p>\n<p></p>\n<div style="text-align: right;"><span style="font-size: 12px; line-height: 24.4799995422363px; color: rgb(0, 0, 0);">Date: {{dateQuoted}}</span></div>\n<br><p></p>\n</td>\n</tr>\n</tbody>\n</table>\n<p><span style="font-size: 18px; line-height: 32.6399993896484px;">{{name}}</span></p>\n<p><span style="font-size: 18px; line-height: 32.6399993896484px;"><br></span></p>', '<div style="text-align: center;"><span style="font-size: 10px;">{pageNumber}</span></div>', 'Quote', 10, 10, 10, 25, 1, 15, '2015-07-21 09:40:50', '2016-03-28 09:10:27', '1', '1');
EOQ;
                $pdo->query($sql);
            }
        } catch (\Exception $e) {

        }

        $config = $this->container->get('config');
        $tabList = $config->get('tabList');

        if (!in_array('Quote', $tabList)) {
            $tabList[] = 'Quote';
            $config->set('tabList', $tabList);
        }
        if (!in_array('Product', $tabList)) {
            $tabList[] = 'Product';
            $config->set('tabList', $tabList);
        }

        if (!in_array('Report', $tabList)) {
            $tabList[] = 'Report';
            $config->set('tabList', $tabList);
        }

        $iframeUrl = $this->addLinkParam($config->get('adminPanelIframeUrl'), 'advanced-pack', 'bcac485dee9efd0f36cf6842ad5b69b4');
        $config->set('adminPanelIframeUrl', $iframeUrl);

        $config->save();

        $this->clearCache();
    }

    protected function clearCache()
    {
        try {
            $this->container->get('dataManager')->clearCache();
        } catch (\Exception $e) {}
    }

    protected function addLinkParam($link, $paramName, $paramValue)
    {
        if (empty($link)) {
            $link = 'https://s.espocrm.com/';
        }

        $param = $paramName . '=' . $paramValue;

        if (preg_match('/\?.*'. $paramName .'=/i', $link)) {
            return preg_replace('/'.$paramName.'=[^&]+/i', $param, $link);
        }

        if (parse_url($link, PHP_URL_QUERY)) {
            return $link . '&' . $param;
        }

        if (preg_match('/(\/$|\/\/.*\/.*\..*$)/', $link)) {
            return $link . '?' . $param;
        }

        return $link . '/?' . $param;
    }
}
