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

Core.define('advanced:views/report/filters/container-complex', ['views/record/base', 'Model'], function (Dep, Model) {

    return Dep.extend({

        template: 'advanced:report/filters/container-complex',

        events: {
            'click > div > a[data-action="removeGroup"]': function () {
                this.trigger('remove-item');
            }
        },

        setup: function () {
            var model = this.model = new Model;
            model.name = 'Report';

            Dep.prototype.setup.call(this);

            this.scope = this.options.scope;

            this.filterData = this.options.filterData || {};

            var params = this.filterData.params || {};

            var functionList = this.getMetadata().get(['entityDefs', 'Report', 'complexExpressionFunctionList']) || [];
            functionList = Core.Utils.clone(functionList);
            functionList.unshift('');

            var operatorList = this.getMetadata().get(['entityDefs', 'Report', 'complexExpressionOperatorList']) || [];

            model.set({
                'function': params.function,
                attribute: params.attribute,
                operator: params.operator,
                value: params.value
            });

            this.createView('function', 'views/fields/enum', {
                el: this.getSelector() + ' .function-container',
                params: {
                    options: functionList
                },
                name: 'function',
                model: model,
                mode: 'edit'
            }, function (view) {
                this.listenTo(view, 'after:render', function () {
                    view.$el.find('.form-control').addClass('input-sm');
                }, this);
            });

            this.createView('operator', 'views/fields/enum', {
                el: this.getSelector() + ' .operator-container',
                params: {
                    options: operatorList
                },
                name: 'operator',
                model: model,
                mode: 'edit'
            }, function (view) {
                this.listenTo(view, 'after:render', function () {
                    view.$el.find('.form-control').addClass('input-sm');
                }, this);
            });

            this.setupAttributes();

            this.createView('attribute', 'views/fields/enum', {
                el: this.getSelector() + ' .attribute-container',
                params: {
                    options: this.attributeList,
                    translatedOptions: this.translatedAttributes
                },
                name: 'attribute',
                model: model,
                mode: 'edit'
            }, function (view) {
                this.listenTo(view, 'after:render', function () {
                    view.$el.find('.form-control').addClass('input-sm');
                }, this);
            });

            this.createView('value', 'views/fields/formula', {
                el: this.getSelector() + ' .value-container',
                params: {
                    height: 50
                },
                name: 'value',
                model: model,
                mode: 'edit'
            });

            this.controlVisibility();
            this.listenTo(this.model, 'change:operator', function () {
                this.controlVisibility();
            }, this);
        },

        controlVisibility: function () {
            if (~['isNull', 'isNotNull', 'isTrue', 'isFalse'].indexOf(this.model.get('operator'))) {
                this.hideField('value');
            } else {
                this.showField('value');
                if (this.getField('value') && this.getField('value').isRendered()) {
                    this.getField('value').reRender();
                }
            }
        },

        getAttributeForScope: function (entityType) {
            var fieldList = this.getFieldManager().getScopeFieldList(entityType).filter(function (item) {
                var defs = this.getMetadata().get(['entityDefs', entityType, 'fields', item]) || {};
                if (defs.notStorable) return;
                if (!defs.type) return;
                if (~['linkMultiple'].indexOf(defs.type)) return;
                return true;
            }, this);

            var attributeList = [];

            fieldList.forEach(function (item) {
                var defs = this.getMetadata().get(['entityDefs', entityType, 'fields', item]) || {};
                this.getFieldManager().getAttributeList(defs.type, item).forEach(function (attr) {
                    if (~attributeList.indexOf(attr)) return;
                    attributeList.push(attr);
                }, this);
            }, this);

            attributeList.sort();

            return attributeList;
        },

        setupAttributes: function () {
            var entityType = this.scope;

            var attributeList = this.getAttributeForScope(entityType);

            var links = this.getMetadata().get(['entityDefs', this.options.scope, 'links']);
            var linkList = [];
            Object.keys(links).forEach(function (link) {
                var type = links[link].type;
                if (!type) return;

                if (~['belongsToParent', 'hasOne', 'belongsTo'].indexOf(type)) {
                    linkList.push(link);
                }
            }, this);
            linkList.sort();
            linkList.forEach(function (link) {
                var scope = links[link].entity;
                if (!scope) return;
                var linkAttributeList = this.getAttributeForScope(scope);
                linkAttributeList.forEach(function (item) {
                    attributeList.push(link + '.' + item);
                }, this);
            }, this);

            this.attributeList = attributeList;

            this.translatedAttributes = {};
        },

        fetch: function () {
            this.getView('function').fetchToModel();
            this.getView('attribute').fetchToModel();
            this.getView('operator').fetchToModel();
            this.getView('value').fetchToModel();

            var data = {
                id: this.filterData.id,
                type: 'complexExpression',
                params: {
                    'function': this.model.get('function'),
                    'attribute': this.model.get('attribute'),
                    'operator': this.model.get('operator'),
                    'value': this.model.get('value')
                }
            };

            return data;
        }

    });
});
