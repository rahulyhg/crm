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

Core.define('Advanced:Views.Workflow.Record.Conditions', 'View', function (Dep) {

    return Dep.extend({

        template: 'advanced:workflow.record.conditions',

        ingoreFieldList: [],

        events: {
            'click [data-action="addCondition"]': function (e) {
                var $target = $(e.currentTarget);
                var conditionType = $target.data('type');
                var field = $target.data('field');

                this.addCondition(conditionType, field, {}, true);
            },
            'click [data-action="removeCondition"]': function (e) {
                var $target = $(e.currentTarget);
                var id = $target.data('id');
                this.clearView('condition-' + id);

                var $conditionContainer = $target.parent();
                var $container = $conditionContainer.parent();

                $conditionContainer.remove();

                if (!$container.find('.condition').length) {
                    $container.find('.no-data').removeClass('hidden');
                }
            }
        },

        data: function () {
            return {
                fieldList: this.fieldList,
                entityType: this.entityType,
                readOnly: this.readOnly,
                hasFormula: this.hasFormula
            }
        },

        afterRender: function () {
            var conditionsAll = this.model.get('conditionsAll') || [];
            var conditionsAny = this.model.get('conditionsAny') || [];

            var conditionsFormula = this.model.get('conditionsFormula') || '';

            conditionsAll.forEach(function (data) {
                this.addCondition('all', data.fieldToCompare, data);
            }, this);

            conditionsAny.forEach(function (data) {
                this.addCondition('any', data.fieldToCompare, data);
            }, this);

            if (this.hasFormula) {
                this.createView('conditionsFormula', 'views/fields/formula', {
                    name: 'conditionsFormula',
                    model: this.model,
                    mode: this.readOnly ? 'detail' : 'edit',
                    height: 50,
                    el: this.getSelector() + ' .formula-conditions',
                    inlineEditDisabled: true,
                    targetEntityType: this.entityType
                }, function (view) {
                    view.render();
                }, this);
            }
        },

        setup: function () {
            this.entityType = this.scope = this.model.get('entityType');

            this.hasFormula = !!this.getMetadata().get('app.formula.functionList');

            var conditionFieldTypes = this.getMetadata().get('entityDefs.Workflow.conditionFieldTypes') || {};
            var defs = this.getMetadata().get('entityDefs.' + this.entityType + '.fields');

            this.fieldList = Object.keys(defs).filter(function (field) {
                var type = defs[field].type || 'base';
                if (defs[field].disabled) return;

                return !~this.ingoreFieldList.indexOf(field) && (type in conditionFieldTypes);
            }, this).sort(function (v1, v2) {
                 return this.translate(v1, 'fields', this.scope).localeCompare(this.translate(v2, 'fields', this.scope));
            }.bind(this));

            this.lastCid = 0;
            this.readOnly = this.options.readOnly || false;
        },

        addCondition: function (conditionType, field, data, isNew) {
            data = data || {};

            var fieldType = this.getMetadata().get('entityDefs.' + this.entityType + '.fields.' + field + '.type') || 'base';
            var type = this.getMetadata().get('entityDefs.Workflow.conditionFieldTypes.' + fieldType) || 'base';

            var $container = this.$el.find('.' + conditionType.toLowerCase() + '-conditions');

            $container.find('.no-data').addClass('hidden');

            var id = data.cid  = this.lastCid;
            this.lastCid++;

            var fieldNameHtml = '<label class="field-label-name control-label">' + this.translate(field, 'fields', this.entityType) + '</label>';
            var removeLinkHtml = this.readOnly ? '' : '<a href="javascript:" class="pull-right" data-action="removeCondition" data-id="'+id+'"><span class="glyphicon glyphicon-remove"></span></a>';
            var html = '<div class="cell form-group" style="margin-left: 20px;">' + removeLinkHtml + fieldNameHtml + '<div class="condition small" data-id="' + id + '"></div></div>';

            $container.append($(html));

            this.createView('condition-' + id, 'Advanced:Workflow.Conditions.' + Core.Utils.upperCaseFirst(type), {
                el: this.options.el + ' .condition[data-id="' + id + '"]',
                conditionData: data,
                model: this.model,
                field: field,
                entityType: this.entityType,
                type: type,
                fieldType: fieldType,
                conditionType: conditionType,
                isNew: isNew,
                readOnly: this.readOnly
            }, function (view) {
                view.render();
            });
        },

        fetch: function () {
            var conditions = {
                all: [],
                any: []
            };

            for (var i = 0; i < this.lastCid; i++) {
                var view = this.getView('condition-' + i);
                if (view) {
                    if (!(view.conditionType in conditions)) {
                        continue;
                    }
                    var data = view.fetch();
                    data.type = view.conditionType;
                    conditions[view.conditionType].push(data);
                }
            }

            if (this.hasFormula) {
                conditions.formula = (this.getView('conditionsFormula').fetch() || {}).conditionsFormula;
            }

            return conditions;
        },
    });
});


