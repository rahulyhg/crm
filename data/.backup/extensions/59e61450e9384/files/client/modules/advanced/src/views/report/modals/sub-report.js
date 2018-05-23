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

Core.define('Advanced:Views.Report.Modals.SubReport', ['Views.Modal', 'Advanced:ReportHelper'], function (Dep, ReportHelper) {

    return Dep.extend({

        cssName: 'sub-report',

        _template: '<div class="list-container">{{{list}}}</div>',

        className: 'dialog dialog-record',

        backdrop: true,

        setup: function () {
            this.buttonList = [
                {
                    name: 'cancel',
                    label: 'Close'
                }
            ];

            var result = this.options.result;

            var reportHelper = new ReportHelper(this.getMetadata(), this.getLanguage(), this.getDateTime());
            var groupValue = this.options.groupValue;

            this.header = this.model.get('name') + ': ' + reportHelper.formatGroup(result.groupBy[0], groupValue, result);

            this.createView('list', 'Advanced:Record.ListForReport', {
                el: this.options.el + ' .list-container',
                collection: this.collection,
                type: 'listSmall',
                reportId: this.model.id,
                groupValue: groupValue
            });
        },

    });
});

