/*********************************************************************************
 * The contents of this file are subject to the CRM Advanced
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

Core.define('Advanced:Views.Workflow.ActionModals.UpdateRelatedEntity',
    ['Advanced:Views.Workflow.ActionModals.CreateEntity', 'Model'], function (Dep, Model) {

    return Dep.extend({

        template: 'advanced:workflow.action-modals.update-related-entity',

        permittedLinkTypes: ['belongsTo', 'hasMany', 'hasChildren'],

        getLinkOptionsHtml: function () {
            var value = this.actionData.link;

            var list = Object.keys(this.getMetadata().get('entityDefs.' + this.entityType + '.links'));

            var html = '<option value="">--' + this.translate('Select') + '--</option>';

            list.forEach(function (item) {
                var defs = this.getMetadata().get('entityDefs.' + this.entityType + '.links.' + item);

                if (defs.disabled) return;
                if (~this.permittedLinkTypes.indexOf(defs.type)) {
                    if (defs.entityList) {
                        defs.entityList.forEach(function (parentEntity) {
                            var selected = (item === value && this.actionData.parentEntity == parentEntity) ? 'selected' : '';

                            var label = this.translate(item, 'links' , this.entityType) + ' &raquo; ' + this.translate(parentEntity, 'scopeNames');
                            html += '<option value="' + item + '-'+parentEntity+'" ' + selected + ' data-link="'+item+'" data-parent-entity="'+parentEntity+'">' + label + '</option>';
                        }.bind(this));
                    } else {
                        var label = this.translate(item, 'links' , this.entityType);
                        html += '<option value="' + item + '" ' + (item === value ? 'selected' : '') + '>' + label + '</option>';
                    }

                }

            }, this);

            return html;
        },

        setupScope: function (callback) {

            if (this.actionData.link) {
                var scope = this.actionData.parentEntity || this.getMetadata().get('entityDefs.' + this.entityType + '.links.' + this.actionData.link + '.entity');
                this.scope = scope;

                if (scope) {
                    this.wait(true);
                    this.getModelFactory().create(scope, function (model) {
                        this.model = model;

                        (this.actionData.fieldList || []).forEach(function (field) {
                            var attributes = (this.actionData.fields[field] || {}).attributes || {};
                            model.set(attributes, {silent: true});
                        }, this);

                        callback();
                    }, this);
                } else {
                    throw new Error;
                }
            } else {
                this.model = null;
                callback();
            }
        },

        changeLinkAction: function (e) {
            var $option = $(e.currentTarget).find('option[value="'+e.currentTarget.value+'"]');

            var value = e.currentTarget.value;

            delete this.actionData.parentEntity;
            if ($option.attr('data-link')) {
                value = $option.attr('data-link');
                this.actionData.parentEntity = $option.attr('data-parent-entity');
            }

            this.actionData.link = value;

            this.actionData.fieldList.forEach(function (field) {
                this.$el.find('.field-row[data-field="' + field + '"]').remove();
                this.clearView('field-' + field);
            }, this);
            this.actionData.fieldList = [];
            this.actionData.fields = {};

            this.handleLink();
        },

        setupFormulaView: function () {
            var model = new Model;
            if (this.hasFormulaAvailable) {
                model.set('formula', this.actionData.formula || null);

                this.createView('formula', 'views/fields/formula', {
                    name: 'formula',
                    model: model,
                    mode: this.readOnly ? 'detail' : 'edit',
                    height: 100,
                    el: this.getSelector() + ' .field[data-name="formula"]',
                    inlineEditDisabled: true,
                    targetEntityType: this.scope
                }, function (view) {
                    view.render();
                }, this);
            }
        },

    });
});
