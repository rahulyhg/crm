
Core.define('views/settings/fields/default-currency', 'views/fields/enum', function (Dep) {

    return Dep.extend({

        setupOptions: function () {
            this.params.options = Core.Utils.clone(this.getConfig().get('currencyList') || []);
        }

    });

});
