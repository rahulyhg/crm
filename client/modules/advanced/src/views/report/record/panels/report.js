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

Core.define('Advanced:Views.Report.Record.Panels.Report', ['View', 'Advanced:ReportHelper'], function (Dep, ReportHelper) {

    return Dep.extend({

        template: 'advanced:report.record.panels.report',

        setup: function () {
            var type = this.model.get('type');
            var groupBy = this.model.get('groupBy') || [];

            var reportHelper = new ReportHelper(this.getMetadata(), this.getLanguage(), this.getDateTime());

            switch (type) {
                case 'Grid':
                    var depth = this.model.get('depth') || groupBy.length;
                    if (depth < 1 || depth > 2) {
                        throw new Error('Bad report');
                    }
                    var viewName = 'Advanced:Report.Reports.Grid' + depth.toString();
                    this.createView('report', viewName, {
                        el: this.options.el + ' .report-container',
                        model: this.model,
                        reportHelper: reportHelper
                    });
                    break;
                case 'List':
                    var viewName = 'Advanced:Report.Reports.List';
                    this.createView('report', viewName, {
                        el: this.options.el + ' .report-container',
                        model: this.model,
                        reportHelper: reportHelper
                    });
                    break;

            }

        },

        afterRender: function () {

        },

        actionRefresh: function () {
            var report = this.getView('report');
            if (!report.hasRuntimeFilters()) {
                report.run();
            }
        }

    });

});

