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

Core.define('Advanced:Views.Report.Reports.Tables.Grid1', ['View', 'Advanced:Views.Report.Reports.Tables.Grid2'], function (Dep, Grid2) {

    return Dep.extend({

        template: 'advanced:report.reports.tables.table',

        setup: function () {
            this.column = this.options.column;
            this.result = this.options.result;
            this.reportHelper = this.options.reportHelper;
        },


        formatCellValue: function (value, column, isTotal) {
            return Grid2.prototype.formatCellValue.call(this, value, column, isTotal);
        },

        formatNumber: function (value, isCurrency) {
            return Grid2.prototype.formatNumber.call(this, value, isCurrency);
        },

        afterRender: function () {
            var result = this.result;

            var groupBy = this.result.groupBy[0];

            $table = $('<table>').addClass('table').addClass('table-bordered');
            var $tr = $('<tr>');
            $tr.append($('<th>'));

            this.result.columns.forEach(function (col) {
                $tr.append($('<th>').html(this.reportHelper.formatColumn(col, this.result) + '&nbsp;'));
            }, this);
            $table.append($tr);

            var reportData = this.options.reportData;

            this.result.grouping[0].forEach(function (gr) {
                var $tr = $('<tr>');
                var html = '<a href="javascript:" data-action="showSubReport" data-group-value="'+gr+'">' + this.reportHelper.formatGroup(groupBy, gr, this.result) + '</a>&nbsp;';
                $tr.append($('<td>').html(html));

                this.result.columns.forEach(function (col) {
                    var value = 0;
                    if (gr in result.reportData) {
                        value = result.reportData[gr][col] || value;
                    }
                    $tr.append($('<td align="right">').html(this.formatCellValue(value, col)));
                }, this);

                $table.append($tr);
            }, this);

            var $tr = $('<tr>');

            $tr.append($('<td>').html('<b>' + this.translate('Total', 'labels', 'Report') + '</b>'));
            this.result.columns.forEach(function (col) {
                value = result.sums[col] || 0;

                $tr.append($('<td align="right">').html('<b>' + this.formatCellValue(value, col, true) + '</b>' + ''));
            }, this);

            $table.append($tr);

            this.$el.find('.table-container').append($table);
        }

    });

});

