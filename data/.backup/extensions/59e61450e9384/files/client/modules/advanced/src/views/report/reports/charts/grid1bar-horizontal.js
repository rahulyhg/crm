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

Core.define('Advanced:Views.Report.Reports.Charts.Grid1BarHorizontal', 'Advanced:Views.Report.Reports.Charts.Grid1BarVertical', function (Dep) {

    return Dep.extend({

        init: function () {
            Dep.prototype.init.call(this);
            if (this.options.color) {
                this.colorList = [this.options.color];
            }
        },

        prepareData: function () {
            var result = this.result;

            var grList = this.grList = _.clone(result.grouping[0]);

            grList.reverse();

            var columns = [];

            var i = 0;

            var data = [[]];

            this.values = [];

            grList.forEach(function (gr) {
                var value = (this.result.reportData[gr] || {})[this.column] || 0;
                this.values.push(value);
                data[0].push([
                    value, i
                ]);
                i++;
            }, this);

            return data;
        },

        drow: function () {
            var self = this;

            this.$graph = this.flotr.draw(this.$container.get(0), this.chartData, {
                shadowSize: false,
                colors: this.colorList,
                bars: {
                    show: true,
                    horizontal: true,
                    shadowSize: 0,
                    lineWidth: 1,
                    fillOpacity: 1,
                    barWidth: 0.5,
                },
                grid: {
                    horizontalLines: false,
                    verticalLines: true,
                    outline: 'sw',
                    color: this.outlineColor
                },
                yaxis: {
                    min: 0,
                    noTicks: 10,
                    tickFormatter: function (value) {
                        if (value % 1 == 0) {
                            var i = parseInt(value);
                            if (i in self.grList) {
                                return self.formatGroup(0, self.grList[i]);
                            }
                        }
                        return '';
                    },
                },
                xaxis: {
                    min: 0,
                    showLabels: true,
                    tickFormatter: function (value) {
                        if (value % 1 == 0) {
                            return Math.floor(value).toString();
                        }
                        return '';
                    },
                },
                mouse: {
                    track: true,
                    relative: true,
                    position: 'w',
                    trackFormatter: function (obj) {
                        var i = Math.floor(obj.y);
                        return self.formatGroup(0, self.grList[i])  + ':<br>' + self.values[i];
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

