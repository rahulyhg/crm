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

Core.define('Advanced:Views.Report.Reports.Charts.Grid1Pie', 'Advanced:Views.Report.Reports.Charts.Grid1BarVertical', function (Dep) {

    return Dep.extend({

        prepareData: function () {
            var result = this.result;
            var grList = this.grList = result.grouping[0];

            var data = [];
            this.values = [];

            grList.forEach(function (gr, i) {
                var value = (this.result.reportData[gr] || {})[this.column] || 0;
                this.values.push(value);

                var o = {
                    label: this.formatGroup(0, gr),
                    data: [[0, value]],
                    value: value
                };

                if (gr in this.colors) {
                    o.color = this.colors[gr];
                }

                data.push(o);

            }, this);

            return data;
        },

        drow: function () {
            var self = this;

            this.$graph = this.flotr.draw(this.$container.get(0), this.chartData, {
                shadowSize: false,
                colors: this.colorListAlt,
                pie: {
                    show: true,
                    fillOpacity: 1,
                    explode: 0,
                    lineWidth: 1,
                },
                grid: {
                    horizontalLines: false,
                    verticalLines: false,
                    outline: '',
                    color: this.outlineColor
                },

                yaxis: {
                    showLabels: false
                },
                xaxis: {
                    showLabels: false
                },
                mouse: {
                    track: true,
                    relative: true,
                    trackFormatter: function (obj) {
                        var value = self.formatNumber(obj.series.value);
                        return (obj.series.label || self.translate('-Empty-', 'labels', 'Report')) + ':<br>' + value;
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

