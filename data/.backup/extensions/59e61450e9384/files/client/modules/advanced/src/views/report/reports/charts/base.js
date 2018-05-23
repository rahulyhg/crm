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

Core.define('Advanced:Views.Report.Reports.Charts.Base', ['View', 'lib!Flotr'], function (Dep, Flotr) {

    return Dep.extend({

        template: 'advanced:report.reports.charts.chart',

        decimalMark: '.',

        thousandSeparator: ',',

        colorList: ['#6FA8D6', '#4E6CAD', '#EDC555', '#ED8F42', '#DE6666', '#7CC4A4', '#8A7CC2', '#D4729B'],

        colorListAlt: ['#6FA8D6', '#EDC555', '#ED8F42', '#7CC4A4', '#D4729B'],

        successColor: '#5ABD37',

        outlineColor: '#333',

        init: function () {
            Dep.prototype.init.call(this);

            this.flotr = this.Flotr = Flotr;

            if (this.options.colorList && this.options.colorList.length) {
                this.colorList = this.options.colorList;
                this.colorListAlt = this.options.colorList;
            }

            this.colors = this.options.colors || {};

            if (this.getPreferences().has('decimalMark')) {
                this.decimalMark = this.getPreferences().get('decimalMark')
            } else {
                if (this.getConfig().has('decimalMark')) {
                    this.decimalMark = this.getConfig().get('decimalMark')
                }
            }
            if (this.getPreferences().has('thousandSeparator')) {
                this.thousandSeparator = this.getPreferences().get('thousandSeparator')
            } else {
                if (this.getConfig().has('thousandSeparator')) {
                    this.thousandSeparator = this.getConfig().get('thousandSeparator')
                }
            }

            var number = Math.floor((Math.random() * 10000) + 1).toString;

            this.once('after:render', function () {
                $(window).on('resize.report-chart-'+number, function () {
                    this.drow();
                }.bind(this));
            }, this);

            this.listenToOnce(this, 'remove', function () {
                $(window).off('resize.report-chart-'+number);
                if (this.$graph) {
                    this.$graph.destroy();
                }
            }, this);

            this.result = this.options.result;
            this.column = this.options.column;
            this.reportHelper = this.options.reportHelper;
        },

        formatNumber: function (value) {
            if (value !== null) {
                var parts = value.toString().split(".");
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);
                if (parts[1] == 0) {
                    parts.splice(1, 1);
                }
                return parts.join(this.decimalMark);
            }
            return '';
        },

        afterRender: function () {
            this.chartData = this.prepareData();

            var $container = this.$container = this.$el.find('.chart-container');

            var height = this.options.height || '350px';
            $container.css('height', height);

            setTimeout(function () {
                this.drow();
            }.bind(this), 1);
        },

    });

});

