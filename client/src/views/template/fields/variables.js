
Core.define('views/template/fields/variables', 'views/fields/base', function (Dep) {

    return Dep.extend({

        inlineEditDisabled: true,

        detailTemplate: 'template/fields/variables/detail',

        editTemplate: 'template/fields/variables/edit',

        data: function () {
            return {
                attributeList: this.attributeList,
                entityType: this.model.get('entityType'),
                translatedOptions: this.translatedOptions
            };
        },

        events: {
            'change [name="variables"]': function () {
                var attribute = this.$el.find('[name="variables"]').val();
                if (attribute != '') {
                    this.$el.find('[name="copy"]').val('{{' + attribute + '}}');
                } else {
                    this.$el.find('[name="copy"]').val('');
                }
            }
        },

        setup: function () {
            this.setupAttributeList();
            this.setupTranslatedOptions();

            this.listenTo(this.model, 'change:entityType', function () {
                this.setupAttributeList();
                this.setupTranslatedOptions();
                this.reRender();
            }, this);
        },

        setupAttributeList: function () {
            var entityType = this.model.get('entityType');

            var attributeList = this.getFieldManager().getEntityAttributes(entityType) || [];

            var forbiddenList = this.getAcl().getScopeForbiddenAttributeList(entityType);
            attributeList = attributeList.filter(function (item) {
                if (~forbiddenList.indexOf(item)) return;
                return true;
            }, this);

            attributeList.push('id');
            if (this.getMetadata().get('entityDefs.' + entityType + '.fields.name.type') == 'personName') {
                attributeList.unshift('name');
            };
            attributeList = attributeList.sort(function (v1, v2) {
                return this.translate(v1, 'fields', entityType).localeCompare(this.translate(v2, 'fields', entityType));
            }.bind(this));

            this.attributeList = attributeList;

            attributeList.unshift('');

            this.translatedOptions = {};
            attributeList.forEach(function (item) {
                this.translatedOptions[item] = this.translate(item, 'fields', entityType);
            }, this);

            var links = this.getMetadata().get('entityDefs.' + entityType + '.links') || {};

            var linkList = Object.keys(links).sort(function (v1, v2) {
                return this.translate(v1, 'links', entityType).localeCompare(this.translate(v2, 'links', entityType));
            }.bind(this));

            linkList.forEach(function (link) {
                var type = links[link].type
                if (type != 'belongsTo') return;
                var scope = links[link].entity;
                if (!scope) return;

                var attributeList = this.getFieldManager().getEntityAttributes(scope) || [];

                var forbiddenList = this.getAcl().getScopeForbiddenAttributeList(scope);
                attributeList = attributeList.filter(function (item) {
                    if (~forbiddenList.indexOf(item)) return;
                    return true;
                }, this);

                attributeList.push('id');
                if (this.getMetadata().get('entityDefs.' + scope + '.fields.name.type') == 'personName') {
                    attributeList.unshift('name');
                };

                attributeList.sort(function (v1, v2) {
                    return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
                }.bind(this));

                attributeList.forEach(function (item) {
                    this.attributeList.push(link + '.' + item);
                }, this);
            }, this);

            return this.attributeList;
        },

        setupTranslatedOptions: function () {
            this.translatedOptions = {};

            var entityType = this.model.get('entityType');
            this.attributeList.forEach(function (item) {
                var field = item;
                var scope = entityType;
                var isForeign = false;
                if (~item.indexOf('.')) {
                    isForeign = true;
                    field = item.split('.')[1];
                    var link = item.split('.')[0];
                    scope = this.getMetadata().get('entityDefs.' + entityType + '.links.' + link + '.entity');
                }
                this.translatedOptions[item] = this.translate(field, 'fields', scope);
                if (isForeign) {
                    this.translatedOptions[item] =  this.translate(link, 'links', entityType) + '.' + this.translatedOptions[item];
                }
            }, this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
        }

    });

});
