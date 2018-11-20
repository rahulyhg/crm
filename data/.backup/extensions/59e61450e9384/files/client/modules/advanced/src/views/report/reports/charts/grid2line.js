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

Core.define('Advanced:Views.Report.Reports.Charts.Grid2Line', 'Advanced:Views.Report.Reports.Charts.Grid2BarVertical', function (Dep) {

    return Dep.extend({

        drow: function () {
            var self = this;

            this.$graph = this.flotr.draw(this.$container.get(0), this.chartData, {
                shadowSize: false,
                colors: this.colorList,
                lines: {
                    show: true,
                },
                grid: {
                    horizontalLines: true,
                    verticalLines: true,
                    outline: 'sw',
                    color: this.outlineColor
                },
                yaxis: {
                    min: 0,
                    showLabels: true,
                    autoscale: true,
                    autoscaleMargin: 1,
                    tickFormatter: function (value) {
                        if (value != 0 && value % 1 == 0) {
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
                mouse: {
                    track: true,
                    relative: true,
                    trackFormatter: function (obj) {
                        return self.sums[Math.floor(obj.x)];
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

