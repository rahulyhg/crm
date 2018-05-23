

Core.define('crm:views/dashlets/sales-by-month', 'crm:views/dashlets/abstract/chart', function (Dep) {

    return Dep.extend({

        name: 'SalesByMonth',

        setupDefaultOptions: function () {
            this.defaultOptions['dateFrom'] = this.defaultOptions['dateFrom'] || moment().format('YYYY') + '-01-01';
            this.defaultOptions['dateTo'] = this.defaultOptions['dateTo'] || moment().format('YYYY') + '-12-31';
        },

        url: function () {
            return 'Opportunity/action/reportSalesByMonth?dateFrom=' + this.getOption('dateFrom') + '&dateTo=' + this.getOption('dateTo');
        },

        prepareData: function (response) {
            var months = this.months = Object.keys(response).sort();

            var values = [];

            for (var month in response) {
                values.push(response[month]);
            }

            this.chartData = [];

            var mid = 0;
            if (values.length) {
                mid = values.reduce(function(a, b) {return a + b}) / values.length;
            }

            var data = [];

            values.forEach(function (value, i) {
                data.push({
                    data: [[i, value]],
                    color: (value >= mid) ? this.successColor : this.colorBad
                });
            }, this);

            return data;
        },

        setup: function () {
            this.currency = this.getConfig().get('defaultCurrency');
            this.currencySymbol = '';

            this.colorBad = this.successColor;
        },

        drow: function () {
            var self = this;
            this.flotr.draw(this.$container.get(0), this.chartData, {
                shadowSize: false,
                bars: {
                    show: true,
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
                        if (value == 0) {
                            return '';
                        }
                        return self.formatNumber(value) + ' ' + self.currency;
                    },
                },
                xaxis: {
                    min: 0,
                    tickFormatter: function (value) {
                        if (value % 1 == 0) {
                            var i = parseInt(value);
                            if (i in self.months) {
                                return moment(self.months[i] + '-01').format('MMM YYYY');
                            }
                        }
                        return '';
                    }
                },
                mouse: {
                    track: true,
                    relative: true,
                    trackFormatter: function (obj) {
                        return self.formatNumber(obj.y) + ' ' + self.currency;
                    },
                }
            });
        },
    });
});


