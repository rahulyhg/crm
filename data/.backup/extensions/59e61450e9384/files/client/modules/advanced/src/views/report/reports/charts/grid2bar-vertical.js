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

Core.define('Advanced:Views.Report.Reports.Charts.Grid2BarVertical', 'Advanced:Views.Report.Reports.Charts.Base', function (Dep) {

    return Dep.extend({

        prepareData: function () {
            var result = this.result;

            var firstList = this.firstList = result.grouping[0];
            var secondList = this.secondList = result.grouping[1];

            if (secondList.length > 5) {
                this.colorList = this.colorList;
            } else {
                this.colorList = this.colorListAlt;
            }

            var columns = [];

            var i = 0;

            this.sums = [];

            firstList.forEach(function (gr1) {
                var d = {};
                var sum = 0;
                secondList.forEach(function (gr2) {
                    if (result.reportData[gr1] && result.reportData[gr1][gr2]) {
                        d[gr2] = result.reportData[gr1][gr2][this.column] || 0;
                    }
                }, this);
                columns.push(d);

                sum = (result.sums[gr1] || {})[this.column] || 0;
                this.sums.push(sum);

                i++;
            }, this);

            var dataByGroup2 = {};

            secondList.forEach(function (gr2) {
                dataByGroup2[gr2] = [];
                columns.forEach(function (d, i) {
                    dataByGroup2[gr2].push([i, d[gr2] || 0]);
                }, this);
            }, this);

            var data = [];
            secondList.forEach(function (gr2, i) {
                var o = {
                    data: dataByGroup2[gr2],
                    label: this.formatGroup(1, gr2)
                };
                if (this.result.success && this.result.success == gr2) {
                    o.color = this.successColor;
                }

                if (gr2 in this.colors) {
                    o.color = this.colors[gr2];
                }
                data.push(o);
            }, this);

            return data;
        },

        formatGroup: function (i, value) {
            var gr = this.result.groupBy[i];
            return this.reportHelper.formatGroup(gr, value, this.result);
        },

        drow: function () {
            var self = this;

            this.$graph = this.flotr.draw(this.$container.get(0), this.chartData, {
                shadowSize: false,
                colors: this.colorList,
                bars: {
                    show: true,
                    stacked : true,
                    horizontal: false,
                    shadowSize: 0,
                    lineWidth: 1,
                    fillOpacity: 1,
                    barWidth: 0.5,
                },
                grid: {
                    horizontalLines: true,
                    verticalLines: false,
                    outline: 'sw',
                    color: this.outlineColor
                },
                yaxis: {
                    min: 0,
                    showLabels: true,
                    tickFormatter: function (value) {
                        if (value == 0) return '';
                        if (value % 1 == 0) {
                            return Math.floor(value).toString();
                        }
                        return '';
                    },
                },
                xaxis: {
                    min: 0,
                    tickFormatter: function (value) {
                        if (value % 1 == 0) {
                            var i = parseInt(value);
                            if (i in self.firstList) {
                                return self.formatGroup(0, self.firstList[i]);
                            }
                        }
                        return '';
                    },
                },

                legend: {
                    show: true,
                    noColumns: 8,
                    container: this.$el.find('.legend-container'),
                    labelBoxMargin: 0
                },
            });
        },
    });

});

