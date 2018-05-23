

Core.define('views/fields/currency-converted', 'views/fields/currency', function (Dep) {

    return Dep.extend({

        data: function () {
            var currencyValue = this.getConfig().get('baseCurrency');
            return _.extend({
                currencyValue: currencyValue,
                currencySymbol: this.getMetadata().get(['app', 'currency', 'symbolMap', currencyValue]) || ''
            }, Dep.prototype.data.call(this));
        },

    });
});

