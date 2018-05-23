/*********************************************************************************
 * The contents of this file are subject to the Samex CRM Advanced
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

Core.define('Advanced:Views.Report.Reports.Grid1', 'Advanced:Views.Report.Reports.Base', function (Dep) {

    return Dep.extend({

        setup: function () {
            this.initReport();
        },

        export: function () {
            var where = this.getRuntimeFilters();

            var o = {
                scope: this.model.get('entityType'),
                reportType: 'Grid'
            };

            var data = {};

            this.createView('dialogExport', 'advanced:views/report/modals/export-grid', o, function (view) {
                view.render();
                this.listenToOnce(view, 'proceed', function (dialogData) {
                    data.where = where;
                    data.format = dialogData.format;

                    if (data.format === 'csv') {
                        var url =
                        this.getBasePath() +
                        '?entryPoint=reportAsCsv&id=' + this.model.id + '&where=' + encodeURIComponent(JSON.stringify(where));
                    } else if (data.format === 'xlsx') {
                        var url =
                        this.getBasePath() +
                        '?entryPoint=reportAsXlsx&id=' + this.model.id + '&where=' + encodeURIComponent(JSON.stringify(where));
                    }
                    window.location = url;

                }, this);
            }, this);
        },

        run: function () {
            this.notify('Please wait...');

            $container = this.$el.find('.report-results-container');
            $container.empty();
            var where = this.getRuntimeFilters();
            $.ajax({
                url: 'Report/action/run',
                data: {
                    id: this.model.id,
                    where: where
                },
            }).done(function (result) {
                this.notify(false);

                this.result = result;

                this.storeRuntimeFilters();

                $tableContainer = $('<div>').addClass('report-table');

                $container.append($tableContainer);

                if (this.chartType) {
                    result.columns.forEach(function (column, i) {
                        $column = $('<div>').addClass('column-' + i).css('margin-bottom', '30px');
                        $header = $('<h4>' + this.options.reportHelper.formatColumn(column, result) + '</h4>');
                        $chartContainer = $('<div>').addClass('report-chart').addClass('report-chart-' + i).css({
                            'overflow-y': 'auto',
                            'margin-bottom': '30px'
                        });

                        $column.append($header);
                        $column.append($chartContainer);
                        $container.append($column);
                    }, this);
                }

                this.createView('reportTable', 'Advanced:Report.Reports.Tables.Grid1', {
                    el: this.options.el + ' .report-results-container .report-table',
                    result: result,
                    reportHelper: this.options.reportHelper
                }, function (view) {
                    view.render();
                });

                result.columns.forEach(function (column, i) {
                    if (this.chartType) {
                        this.createView('reportChart' + i, 'Advanced:Report.Reports.Charts.Grid1' + this.chartType, {
                            el: this.options.el + ' .report-results-container .column-' + i + ' .report-chart',
                            column: column,
                            result: result,
                            reportHelper: this.options.reportHelper,
                            colors: result.chartColors || {},
                            color: result.chartColor || null
                        }, function (view) {
                            view.render();
                        }, this);
                    }
                }, this);


            }.bind(this));
        },

        getPDF: function (id, where) {
            this.getRouter();
        }

    });

});

