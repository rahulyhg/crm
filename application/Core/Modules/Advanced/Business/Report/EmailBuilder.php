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

namespace Core\Modules\Advanced\Business\Report;

use \Core\ORM\Entity;
use \Core\Core\Utils\DateTime;
use \Core\Core\Exceptions\Error;

class EmailBuilder
{
    protected $entityManager;

    protected $smtpParams;

    protected $mailSender;

    protected $config;

    protected $dateTime;

    protected $metadata;

    protected $language;

    protected $htmlizer;

    protected $user;

    protected $preferences;

    protected $templateFileManager;

    public function __construct($metadata, $entityManager, $smtpParams, $mailSender, $config, $language, $htmlizer, $templateFileManager)
    {
        $this->metadata = $metadata;
        $this->entityManager = $entityManager;
        $this->smtpParams = $smtpParams;
        $this->mailSender = $mailSender;
        $this->config = $config;
        $this->language = $language;
        $this->htmlizer = $htmlizer;
        $this->templateFileManager = $templateFileManager;
    }

    protected function getHtmlizer()
    {
        return $this->htmlizer;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getTemplateFileManager()
    {
        return $this->templateFileManager;
    }

    protected function getLanguage()
    {
        return $this->language;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function initForUserById($userId)
    {
        $this->user = $this->getEntityManager()->getEntity('User', $userId);
        if (!$this->user) {
            throw Error('No User with id = ' . $userId);
        }
        $this->preferences = $this->getEntityManager()->getEntity('Preferences', $userId);
        $this->language->setLanguage($this->getPreference('language'));
        $this->dateTime = new DateTime(
            $this->getPreference('dateFormat'),
            $this->getPreference('timeFormat'),
            $this->getPreference('timeZone'));
    }

    protected function getPreference($attribute)
    {
        $hasAttr = true;
        switch ($attribute) {
            case 'weekStart': $hasAttr = ($this->preferences->get($attribute) == -1) ? false : true;
            default: $hasAttr = ($this->preferences->get($attribute) == '') ? false : true;
        }
        return ($hasAttr) ? $this->preferences->get($attribute) : $this->getConfig()->get($attribute);
    }

    public function buildEmailData(&$data, $reportResult, $report)
    {
        if (!is_object($report)) {
            return false;
        }
        if (!is_array($data) || !isset($data['userId'])) {
            $GLOBALS['log']->error('Report Sending: Not enough data for sending email. ' . print_r($data, true));
            return false;
        }
        $this->initForUserById($data['userId']);

        $type = $report->get('type');
        switch ($type) {
            case 'Grid':
                $this->buildEmailGridData($data, $reportResult, $report);
                break;
            case 'List':
                $this->buildEmailListData($data, $reportResult, $report);
                break;
        }

        return true;
    }

    protected function buildEmailGridData(&$data, $reportResult, $report)
    {
        $depth = (int) $reportResult['depth'];
        $methodName = 'buildEmailGrid' . $depth. 'Data';

        if (method_exists($this, $methodName)) {
            $this->$methodName($data, $reportResult, $report);
        } else {
             throw new Error("Unavailable grid type [$depth]");
        }
    }

    protected function buildEmailListData(& $data, $reportResult, $report)
    {
        $entityType = $report->get('entityType');
        $columns = $report->get('columns');
        $columnsDataValue = $report->get('columnsData');
        if ($columnsDataValue instanceof \StdClass) {
            $columnsData = get_object_vars($columnsDataValue);
        } else if (is_array($columnsDataValue)) {
            $columnsData = $columnsDataValue;
        } else {
            $columnsData = [];
        }

        $entity = $this->getEntityManager()->getEntity($entityType);

        if (empty($entity)) {
            return false;
        }

        $fields = $this->getMetadata()->get(['entityDefs', $entityType, 'fields']);

        $columnAttributes = [];
        foreach ($columns as $column) {
            $columnData = (isset($columnsData[$column])) ? $columnsData[$column] : null;
            $attrs = [];
            if (is_object($columnData)) {
                if (isset($columnData->width)) {
                    $attrs['width'] = $columnData->width . '%';
                }
                if (isset($columnData->align)) {
                    $attrs['align'] = $columnData->align;
                }
            }
            $columnAttributes[$column] = $attrs;
        }

        $columnTitles = [];
        foreach ($columns as $column) {
            $field = $column;
            $scope = $entityType;
            $isForeign = false;
            if (strpos($column, '.') !== false) {
                $isForeign = true;
                list($link, $field) = explode('.', $column);
                $scope = $this->getMetadata()->get(['entityDefs', $entityType, 'links', $link, 'entity']);
                $fields[$column] = $this->getMetadata()->get(['entityDefs', $scope, 'fields', $field]);
                $fields[$column]['scope'] = $scope;
                $fields[$column]['isForeign'] = true;
            }
            $label = $this->language->translate($field, 'fields', $scope);
            if ($isForeign) {
                $label = $this->language->translate($link, 'links', $entityType) . '.' . $label;
            }

            $columnTitles[] = [
                'label' => $label,
                'attrs' => $columnAttributes[$column]];
        }

        $rows = [];

        foreach ($reportResult as $recordKey => $record) {

            foreach ($columns as $columnKey => $column) {

                $type = (isset($fields[$column])) ? $fields[$column]['type'] : '';

                $columnInRecord = str_replace('.', '_', $column);
                $value = (isset($record[$columnInRecord])) ? (string) $record[$columnInRecord] : '';

                switch($type) {
                    case 'date':
                        if (!empty($value)) {
                            $value = $this->dateTime->convertSystemDate($value);
                        }
                        break;
                    case 'datetime':
                        if (!empty($value)) {
                            $value = $this->dateTime->convertSystemDateTime($value);
                        }
                        break;
                    case 'link':
                    case 'linkParent':
                        if (!empty($record[$columnInRecord . 'Name'])) {
                            $value = $record[$columnInRecord . 'Name'];
                        }
                        break;
                    case 'jsonArray':break;
                    case 'bool': $value = ($value) ? '1' : '0'; break;
                    case 'enum':
                        if (isset($fields[$column]['isForeign']) && $fields[$column]['isForeign']) {
                            list($link, $field) = explode('.', $column);
                            $value = $this->language->translateOption($value, $field, $fields[$column]['scope']);
                        } else {
                            $value = $this->language->translateOption($value, $column, $entityType);
                        }
                        break;
                    case 'int':
                        $value = $this->formatInt($value);
                        break;
                    case 'float':
                        $value = $this->formatFloat($value);
                        break;
                    case 'currency':
                        $value = $this->formatCurrency($value, $record[$columnInRecord . 'Currency']);
                        break;
                    case 'currencyConverted':
                        $value = $this->formatCurrency($value);
                        break;
                }

                $rows[$recordKey][$columnKey] = [
                    'value' => $value,
                    'attrs' => $columnAttributes[$column]
                ];
            }
        }
        $bodyData = [
            'columnList' => $columnTitles,
            'rows' => $rows,
            'NoData' => $this->language->translate("No Data")
        ];

        $subject = $this->htmlizeTemplate($report, 'subject-list');
        $body = $this->htmlizeTemplate($report, 'body-list', $bodyData);

        $data['emailSubject'] = $subject;
        $data['emailBody'] = $body;
        return true;
    }

    protected function buildEmailGrid1Data(&$data, $reportResult, $report)
    {
        $reportData = $reportResult['reportData'];

        $rows = [];
        $groupName = $reportResult['groupBy'][0];

        $row = [];
        $row[] = ['value' => ''];
        $columnTypes = [];

        foreach ($reportResult['columns'] as $column) {
            $parsebleTypes = ['int', 'float', 'currency', 'currencyConverted'];

            $columnType = 'int';
            if (strpos($column, ':')) {
                list($function, $field) = explode(':', $column);
                if ($function != 'COUNT') {
                    $columnType = $this->getMetadata()->get(['entityDefs', $report->get('entityType'), 'fields', $field, 'type']);
                }
            }
            $columnTypes[$column] = (in_array($columnType, $parsebleTypes)) ? $columnType : 'int';

            $label = $column;
            if (isset($reportResult['columnNameMap'][$column])) {
                $label = $reportResult['columnNameMap'][$column];
            }
            $row[] = [
                'value' => $label,
                'wrapper' => 'b'
            ];
        }
        $rows[] = $row;
        foreach ($reportResult['grouping'][0] as $gr) {
            $row = [];
            $label = $gr;
            if (empty($label)) {
                $label = $this->getLanguage()->translate('-Empty-', 'labels', 'Report');
            } else if (isset($reportResult['groupNameMap'][$groupName][$gr])) {
                $label = $reportResult['groupNameMap'][$groupName][$gr];
            }
            if (strpos($groupName , ':')) {
                list($function, $field) = explode(':', $groupName);
                $label = $this->handleDateGroupValue($function, $label);
            }
            $row[] = ['value' => $label];
            foreach ($reportResult['columns'] as $column) {
                $value = 0;
                if (isset($reportData[$gr])) {
                    if (isset($reportData[$gr][$column])) {
                        $value = $reportData[$gr][$column];
                        switch ($columnTypes[$column]) {
                            case 'int': $value = $this->formatInt($value); break;
                            case 'float': $value = $this->formatFloat($value); break;
                            case 'currency': ;
                            case 'currencyConverted': $value = $this->formatCurrency($value, null, false); break;
                        }
                    }
                }
                $row[] = [
                    'value' => $value,
                    'attrs' => ['align' => 'right']
                ];
            }
            $rows[] = $row;
        }
        $row = [];
        $totalLabel = $this->getLanguage()->translate('Total', 'labels', 'Report');
        $row[] = [
            'value' => $totalLabel,
            'wrapper' => 'b'
        ];
        foreach ($reportResult['columns'] as $column) {
            $sum = 0;

            if (isset($reportResult['sums'][$column])) {
                $sum = $reportResult['sums'][$column];
                switch ($columnTypes[$column]) {
                    case 'int': $sum = $this->formatInt($sum); break;
                    case 'float': $sum = $this->formatFloat($sum); break;
                    case 'currency': ;
                    case 'currencyConverted': $sum = $this->formatCurrency($sum, null, false); break;
                }
            }
            $row[] = [
                'value' => $sum,
                'wrapper' => 'b',
                'attrs' => ['align' => 'right']
            ];
        }
        $rows[] = $row;

        $bodyData = [
            'rows' => $rows,
        ];

        $subject = $this->htmlizeTemplate($report, 'subject-grid-1');
        $body = $this->htmlizeTemplate($report, 'body-grid-1', $bodyData);

        $data['emailSubject'] = $subject;
        $data['emailBody'] = $body;

        return true;
    }

    protected function buildEmailGrid2Data(&$data, $reportResult, $report)
    {
        $reportData = $reportResult['reportData'];
        $parsebleTypes = ['int', 'float', 'currency', 'currencyConverted'];

        $grids = [];
        foreach ($reportResult['columns'] as $column) {
            $grid = [];
            $groupName1 = $reportResult['groupBy'][0];
            $groupName2 = $reportResult['groupBy'][1];

            $columnType = 'int';
            if (strpos($column , ':')) {
                list($function, $field) = explode(':', $column);
                if ($function != 'COUNT') {
                    $columnType = $this->getMetadata()->get(['entityDefs', $report->get('entityType'), 'fields', $field, 'type']);
                }
            }
            $columnTypes[$column] = (in_array($columnType, $parsebleTypes)) ? $columnType : 'int';

            $row = [];
            $row[] = '';
            foreach ($reportResult['grouping'][1] as $gr2) {
                $label = $gr2;
                if (empty($label)) {
                    $label = $this->getLanguage()->translate('-Empty-', 'labels', 'Report');
                } else if (!empty($reportResult['groupNameMap'][$groupName2][$gr2])) {
                    $label = $reportResult['groupNameMap'][$groupName2][$gr2];
                }
                if (strpos($groupName2 , ':')) {
                    list($function, $field) = explode(':', $groupName2);
                    $label = $this->handleDateGroupValue($function, $label);
                }
                $row[] = ['value' => $label];
            }
            $totalLabel = $this->getLanguage()->translate('Total', 'labels', 'Report');
            $row[] = [
                'value' => $totalLabel,
                'wrapper' => 'b'
            ];
            $grid[] = $row;

            foreach ($reportResult['grouping'][0] as $gr1) {
                $row = [];
                $label = $gr1;
                if (empty($label)) {
                    $label = $this->getLanguage()->translate('-Empty-', 'labels', 'Report');
                } else if (isset($reportResult['groupNameMap'][$groupName1][$gr1])) {
                    $label = $reportResult['groupNameMap'][$groupName1][$gr1];
                }
                if (strpos($groupName1 , ':')) {
                    list($function, $field) = explode(':', $groupName1);
                    $label = $this->handleDateGroupValue($function, $label);
                }
                $row[] = [
                    'value' => $label,
                    'wrapper' => 'b'
                ];
                foreach ($reportResult['grouping'][1] as $gr2) {
                    $value = 0;
                    if (isset($reportData[$gr1]) && isset($reportData[$gr1][$gr2])) {
                        if (isset($reportData[$gr1][$gr2][$column])) {
                            $value = $reportData[$gr1][$gr2][$column];
                            switch ($columnType) {
                                case 'int': $value = $this->formatInt($value); break;
                                case 'float': $value = $this->formatFloat($value); break;
                                case 'currency': ;
                                case 'currencyConverted': $value = $this->formatCurrency($value, null, false); break;
                            }
                        }
                    }
                    $row[] = [
                        'value' => $value,
                        'attrs' => ['align' => 'right']
                    ];
                }
                $sum = 0;

                if (isset($reportResult['sums'][$gr1])) {
                    if (isset($reportResult['sums'][$gr1][$column])) {
                        $sum = $reportResult['sums'][$gr1][$column];
                        switch ($columnType) {
                                case 'int': $sum = $this->formatInt($sum); break;
                                case 'float': $sum = $this->formatFloat($sum); break;
                                case 'currency': ;
                                case 'currencyConverted': $sum = $this->formatCurrency($sum, null, false); break;
                            }
                    }
                }
                $row[] = [
                    'value' => $sum,
                    'wrapper' => 'b',
                    'attrs' => ['align' => 'right']
                ];
                $grid[] = $row;
            }

            $rows = [];
            foreach ($grid as $i => $row) {
                foreach ($row as $j => $value) {
                    $rows[$j][$i] = $value;
                }
            }

            $grids[] = [
                'rows' => $rows,
                'header' => $reportResult['columnNameMap'][$column],
            ];
        }

        $bodyData = [
            'grids' => $grids
        ];

        $subject = $this->htmlizeTemplate($report, 'subject-grid-2');
        $body = $this->htmlizeTemplate($report, 'body-grid-2', $bodyData);

        $data['emailSubject'] = $subject;
        $data['emailBody'] = $body;

        return true;
    }

    public function sendEmail($data)
    {
        if (!is_array($data) || !isset($data['userId']) || !isset($data['emailSubject']) || !isset($data['emailBody'])) {
            $GLOBALS['log']->error('Report Sending: Not enough data for sending email. ' . print_r($data, true));
            return false;
        }
        $user = $this->getEntityManager()->getEntity('User', $data['userId']);
        if (empty($user)) {
            $GLOBALS['log']->error('Report Sending: No user with id ' . $data['userId']);
            return false;
        }
        $emailAddress = $user->get('emailAddress');
        if (empty($emailAddress)) {
            $GLOBALS['log']->error('Report Sending: User has no email address');
            return false;
        }
        $email = $this->getEntityManager()->getEntity('Email');

        $email->set([
            'to' => $emailAddress,
            'subject' => $data['emailSubject'],
            'body' => $data['emailBody'],
            'isHtml' => true
        ]);

        $this->getEntityManager()->saveEntity($email);

        $emailSender = $this->mailSender;
        if ($this->smtpParams) {
            $emailSender->useSmtp($this->smtpParams);
        }
        $message = null;
        $attachmentList = [];
        if (isset($data['attachmentId'])) {
            $attachment = $this->getEntityManager()->getEntity('Attachment', $data['attachmentId']);
            $attachmentList[] = $attachment;
        }
        $emailSender->send($email, [], $message, $attachmentList);
        $this->getEntityManager()->removeEntity($email);
        if (isset($attachment)) {
            $this->getEntityManager()->removeEntity($attachment);
        }
    }

    protected function formatCurrency($value, $currency = null, $showCurrency = true)
    {
        if ($value === "") {
            return $value;
        }
        $userThousandSeparator = $this->getPreference('thousandSeparator');
        $userDecimalMark = $this->getPreference('decimalMark');
        $currencyFormat = (int) $this->getConfig()->get('currencyFormat');

        if (!$currency) {
            $currency = $this->getConfig()->get('defaultCurrency');
        }
        if ($currencyFormat) {
            $pad = (int) $this->getConfig()->get('currencyDecimalPlaces');
            $value = number_format($value, $pad, $userDecimalMark, $userThousandSeparator);
        } else {
            $value = $this->formatFloat($value);
        }
        if ($showCurrency) {
            switch ($currencyFormat) {
                case 1:
                    $value = $value . ' ' . $currency;
                    break;
                case 2:
                    $currencySign = $this->getMetadata()->get(['app', 'currency', 'symbolMap', $currency]);
                    $value = $currencySign . $value;
                    break;
            }
        }
        return $value;
    }

    protected function formatInt($value)
    {
        if ($value === "") {
            return $value;
        }
        $userThousandSeparator = $this->getPreference('thousandSeparator');
        $userDecimalMark = $this->getPreference('decimalMark');
        return number_format($value, 0, $userDecimalMark, $userThousandSeparator);
    }

    protected function formatFloat($value)
    {
        if ($value === "") {
            return $value;
        }
        $userThousandSeparator = $this->getPreference('thousandSeparator');
        $userDecimalMark = $this->getPreference('decimalMark');
        return rtrim(rtrim(number_format($value, 8, $userDecimalMark, $userThousandSeparator), '0'), $userDecimalMark);
    }

    protected function htmlizeTemplate($entity, $templateName, array $data = [])
    {
        $systemLanguage = $this->getConfig()->get('language');
        $tpl = $this->getTemplateFileManager()->getTemplate('report-sending', $templateName, null, 'Advanced');
        $tpl = str_replace(["\n", "\r"], '', $tpl);
        return $this->getHtmlizer()->render($entity, $tpl, 'report-sending-' . $templateName . '-' . $systemLanguage, $data, true);
    }

    protected function handleDateGroupValue($function, $value)
    {
        if ($function === 'MONTH') {
            list($year, $month) = explode('-', $value);
            $monthNamesShort = $this->language->get('Global.lists.monthNamesShort');
            $monthLabel = $monthNamesShort[intval($month) - 1];
            $value = $monthLabel . ' ' . $year;
        } else if ($function === 'DAY') {
            $value = $this->dateTime->convertSystemDateToGlobal($value);
        }

        return $value;
    }

}
