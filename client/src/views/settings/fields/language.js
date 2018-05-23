

Core.define('views/settings/fields/language', 'views/fields/enum', function (Dep) {

    return Dep.extend({

        setupOptions: function () {
            this.params.options = Core.Utils.clone(this.getConfig().get('languageList'));
            this.translatedOptions = Core.Utils.clone(this.getLanguage().translate('language', 'options') || {});
        }

    });

});
