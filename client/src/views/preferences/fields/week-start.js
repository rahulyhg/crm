

Core.define('views/preferences/fields/week-start', 'views/fields/enum-int', function (Dep) {

    return Dep.extend({

        setupOptions: function () {
            this.params.options = Core.Utils.clone(this.params.options);
            this.params.options.unshift(-1);

            this.translatedOptions = Core.Utils.clone(this.getLanguage().translate('weekStart', 'options', 'Settings') || {});
            this.translatedOptions[-1] = this.translate('Default') + ' (' + this.getLanguage().translateOption(this.getConfig().get('weekStart'), 'weekStart', 'Settings') +')';
        },

    });

});
