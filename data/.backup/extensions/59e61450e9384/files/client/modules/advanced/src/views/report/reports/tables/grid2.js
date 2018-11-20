/*********************************************************************************
 * The contents of this file are subject to the CRM Advanced
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

Core.define('Advanced:Views.Report.Reports.Tables.Grid2', 'View', function (Dep) {

    return Dep.extend({

        template: 'advanced:report.reports.tables.table',

        setup: function () {
            this.column = this.options.column;
            this.result = this.options.result;
            this.reportHelper = this.options.reportHelper;
        },

        formatGroup: function (i, value) {
            var gr = this.result.groupBy[i];
            return this.reportHelper.formatGroup(gr, value, this.result);
        },

        formatCellValue: function (value, column, isTotal) {
            var entityType = this.result.entityType;

            var arr = column.split(':');
            var isCurrency = false;

            if (arr.length > 1) {
                arr = arr[1].split('.');
                if (arr.length > 1) {
                    var link = arr[0];
                    var field = arr[1];
                    entityType = this.getMetadata().get(['entityDefs', entityType, 'links', link, 'entity']);
                } else {
                    var field = arr[0];
                }
                if (entityType) {
                    var type = this.getMetadata().get(['entityDefs', entityType, 'fields', field, 'type']);
                    isCurrency = !!~['currency', 'currencyConverted'].indexOf(type);
                }
            }

            if (!isTotal && value == 0) {
                if (~column.indexOf('COUNT:')) {
                    return '<span class="text-muted">' + 0 + '</span>';
                }
                return '<span class="text-muted">' + this.formatNumber(0) + '</span>';
            }
            if (~column.indexOf('COUNT:')) {
                return value;
            }
            return this.formatNumber(value, isCurrency);
        },

        formatNumber: function (value, isCurrency) {
            if (!this.decimalMark) {
                if (this.getPreferences().has('decimalMark')) {
                    this.decimalMark = this.getPreferences().get('decimalMark');
                } else {
                    if (this.getConfig().has('decimalMark')) {
                        this.decimalMark = this.getConfig().get('decimalMark');
                    }
                }
                if (this.getPreferences().has('thousandSeparator')) {
                    this.thousandSeparator = this.getPreferences().get('thousandSeparator');
                } else {
                    if (this.getConfig().has('thousandSeparator')) {
                        this.thousandSeparator = this.getConfig().get('thousandSeparator');
                    }
                }
            }

            if (value !== null) {
                var maxDecimalPlaces = 2;
                var currencyDecimalPlaces = this.getConfig().get('currencyDecimalPlaces');

                if (isCurrency) {
                    if (currencyDecimalPlaces === 0) {
                        value = Math.round(value);
                    } else if (currencyDecimalPlaces) {
                        value = Math.round(value * Math.pow(10, currencyDecimalPlaces)) / (Math.pow(10, currencyDecimalPlaces));
                    } else {
                        value = Math.round(value * Math.pow(10, maxDecimalPlaces)) / (Math.pow(10, maxDecimalPlaces));
                    }
                } else {
                    var maxDecimalPlaces = 4;
                    value = Math.round(value * Math.pow(10, maxDecimalPlaces)) / (Math.pow(10, maxDecimalPlaces));
                }

                var parts = value.toString().split(".");
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);

                if (isCurrency) {
                    if (currencyDecimalPlaces === 0) {
                        delete parts[1];
                    } else if (currencyDecimalPlaces) {
                        var decimalPartLength = 0;
                        if (parts.length > 1) {
                            decimalPartLength = parts[1].length;
                        } else {
                            parts[1] = '';
                        }

                        if (currencyDecimalPlaces && decimalPartLength < currencyDecimalPlaces) {
                            var limit = currencyDecimalPlaces - decimalPartLength;
                            for (var i = 0; i < limit; i++) {
                                parts[1] += '0';
                            }
                        }
                    }
                }

                return parts.join(this.decimalMark);
            }
            return '';
        },

        afterRender: function () {
            var result = this.result;

            $table = $('<table>').addClass('table').addClass('table-bordered');
            var $tr = $('<tr>');
            $tr.append($('<th>'));

            this.result.grouping[0].forEach(function (gr1) {
                var html = '<a href="javascript:" data-action="showSubReport" data-group-value="'+gr1+'">' + this.formatGroup(0, gr1) + '</a>&nbsp;';
                $tr.append($('<th>').html(html));
            }, this);
            $table.append($tr);

            var reportData = this.options.reportData;

            this.result.grouping[1].forEach(function (gr2) {
                var $tr = $('<tr>');
                $tr.append($('<td>').html(this.formatGroup(1, gr2) + '&nbsp;'));
                this.result.grouping[0].forEach(function (gr1) {
                    var value = 0;
                    if ((gr1 in result.reportData) && (gr2 in result.reportData[gr1])) {
                        value = result.reportData[gr1][gr2][this.column];
                    }
                    $tr.append($('<td align="right">').html(this.formatCellValue(value, this.column)));
                }, this);

                $table.append($tr);
            }, this);

            var $tr = $('<tr>');

            $tr.append($('<td>').html('<b>' + this.translate('Total', 'labels', 'Report') + '</b>'));
            this.result.grouping[0].forEach(function (gr1) {
                var value = 0;
                if (gr1 in result.sums) {
                    value = result.sums[gr1][this.column];
                }
                $tr.append($('<td align="right">').html('<b>' + this.formatCellValue(value, this.column, true) + '</b>' + ''));
            }, this);

            $table.append($tr);

            this.$el.find('.table-container').append($table);
        }

    });

});

