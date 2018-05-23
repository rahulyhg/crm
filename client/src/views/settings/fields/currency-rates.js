
Core.define('views/settings/fields/currency-rates', 'views/fields/base', function (Dep) {

    return Dep.extend({

        editTemplate: 'settings/fields/currency-rates/edit',

        data: function () {
            var baseCurrency = this.model.get('baseCurrency');
            var currencyRates = this.model.get('currencyRates') || {};

            var rateValues = {};
            (this.model.get('currencyList') || []).forEach(function (currency) {
                if (currency != baseCurrency) {
                    rateValues[currency] = currencyRates[currency] || 1.00;
                }
            }, this);

            return {
                rateValues: rateValues
            };
        },

        setup: function () {
        },

        fetch: function () {
            var data = {};
            var currencyRates = {};

            var baseCurrency = this.model.get('baseCurrency');

            var currencyList = this.model.get('currencyList') || [];

            currencyList.forEach(function (currency) {
                if (currency != baseCurrency) {
                    currencyRates[currency] = parseFloat(this.$el.find('input[data-currency="'+currency+'"]').val() || 1);
                }
            }, this);

            delete currencyRates[baseCurrency];
            for (var c in currencyRates) {
                if (!~currencyList.indexOf(c)) {
                    delete currencyRates[c];
                }
            }

            data[this.name] = currencyRates;

            return data;
        }

    });

});
