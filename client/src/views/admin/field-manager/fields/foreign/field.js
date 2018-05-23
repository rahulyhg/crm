

Core.define('views/admin/field-manager/fields/foreign/field', 'views/fields/enum', function (Dep) {

    return Dep.extend({

        typeList: ['varchar', 'enum', 'enumInt', 'enumFloat', 'int', 'float', 'website'],

        setup: function () {
            Dep.prototype.setup.call(this);
            if (!this.model.isNew()) {
                this.setReadOnly(true);
            }
            this.listenTo(this.model, 'change:field', function () {
                this.manageField();
            }, this);

            this.viewValue = this.model.get('view');
        },

        setupOptions: function () {
            this.listenTo(this.model, 'change:link', function () {
                this.setupOptionsByLink();
                this.reRender();
            }, this);
            this.setupOptionsByLink();
        },

        setupOptionsByLink: function () {
            var link = this.model.get('link');

            if (!link) {
                this.params.options = [''];
                return;
            }

            var scope = this.getMetadata().get(['entityDefs', this.options.scope, 'links', link, 'entity']);

            if (!scope) {
                this.params.options = [''];
                return;
            }

            var fields = this.getMetadata().get(['entityDefs', scope, 'fields']) || {};

            this.params.options = Object.keys(Core.Utils.clone(fields)).filter(function (item) {
                var type = fields[item].type;
                if (!~this.typeList.indexOf(type)) return;
                if (fields[item].notStorable) return;

                return true;
            }, this);

            this.translatedOptions = {};
            this.params.options.forEach(function (item) {
                this.translatedOptions[item] = this.translate(item, 'fields', scope);
            }, this);

            this.params.options.unshift('');
        },

        manageField: function () {
            if (!this.model.isNew()) return;

            var link = this.model.get('link');
            var field = this.model.get('field');

            if (!link || !field) {
                return;
            }
            var scope = this.getMetadata().get(['entityDefs', this.options.scope, 'links', link, 'entity']);
            if (!scope) {
                return;
            }
            var type = this.getMetadata().get(['entityDefs', scope, 'fields', field, 'type']);

            if (type == 'enum') {
                this.viewValue = 'views/fields/foreign-enum';
            } else if (type == 'enumInt') {
                this.viewValue = 'views/fields/foreign-int';
            } else if (type == 'enumFloat') {
                this.viewValue = 'views/fields/foreign-float';
            } else if (type == 'varchar') {
                this.viewValue = 'views/fields/foreign-varchar';
            } else if (type == 'int') {
                this.viewValue = 'views/fields/foreign-int';
            } else if (type == 'float') {
                this.viewValue = 'views/fields/foreign-float';
            } else {
                this.viewValue = null;
            }
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            if (this.model.isNew()) {
                if (this.viewValue) {
                    data['view'] = this.viewValue;
                }
            }
            return data;
        }
    });

});
