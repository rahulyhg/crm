/*********************************************************************************
 * The contents of this file are subject to the CoreCRM Advanced
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

Core.define('Advanced:Views.Workflow.ConditionFields.Subjects.Field', 'View', function (Dep) {

    return Dep.extend({

        template: 'advanced:workflow.condition-fields.subjects.field',

        data: function () {
            return {
                value: this.options.value,
                entityType: this.options.entityType,
                listHtml: this.listHtml,
                readOnly: this.readOnly
            };
        },

        setup: function () {
            this.readOnly = this.options.readOnly;
            var fieldType = this.options.fieldType;
            var entityType = this.options.entityType;
            var field = this.options.field;

            var value = this.options.value;

            var fieldTypeList = this.getMetadata().get('entityDefs.Workflow.fieldTypeComparison.' + fieldType) || [];

            var list = [];
            var fieldDefs = this.getMetadata().get('entityDefs.' + entityType + '.fields');
            Object.keys(fieldDefs).forEach(function (f) {
                if ((fieldDefs[f].type == fieldType || ~fieldTypeList.indexOf(fieldDefs[f].type)) && f != field) {
                    list.push(f);
                }
            }, this);

            if (this.readOnly) {
                if (~value.indexOf('.')) {
                    var values = value.split(".");
                    var foreignScope = this.getMetadata().get('entityDefs.' + entityType + '.links.' + values[0] + '.entity') || entityType;
                    this.listHtml = this.translate(values[0], 'links', entityType) + '.' + this.translate(values[1], 'fields', foreignScope);
                } else {
                    this.listHtml = this.translate(entityType, 'scopeNames') + '.' + this.translate(value, 'fields', entityType);
                }
                return;
            }

            var listHtml = '';

            list.forEach(function (f, i) {
                if (i == 0) {
                    listHtml += '<optgroup label="' + this.translate(entityType, 'scopeNames') + '">';
                }
                var selectedHtml = '';
                if (value == f) {
                    selectedHtml = 'selected';
                }
                listHtml += '<option ' + selectedHtml + ' value="' + f + '">' + this.translate(f, 'fields', entityType) + '</option>';
                if (i == list.length - 1) {
                    listHtml += '</optgroup>';
                }
            }, this);

            var relatedFields = {};

            var linkDefs = this.getMetadata().get('entityDefs.' + entityType + '.links');
            Object.keys(linkDefs).forEach(function (link) {
                var list = [];
                if (linkDefs[link].type == 'belongsTo') {
                    var foreignEntityType = linkDefs[link].entity;
                    if (!foreignEntityType) {
                        return;
                    }
                    var fieldDefs = this.getMetadata().get('entityDefs.' + foreignEntityType + '.fields');
                    Object.keys(fieldDefs).forEach(function (f) {
                        if (fieldDefs[f].type == fieldType || ~fieldTypeList.indexOf(fieldDefs[f].type)) {
                            list.push(f);
                        }
                    }, this);
                    relatedFields[link] = list;
                }
            }, this);

            for (var link in relatedFields) {
                relatedFields[link].forEach(function (f, i) {
                    if (i == 0) {
                        listHtml += '<optgroup label="' + this.translate(link, 'links', entityType) + '">';
                    }

                    var selectedHtml = false;
                    if (value == link + '.' + f) {
                        selectedHtml = 'selected';
                    }

                    listHtml += '<option ' + selectedHtml + ' value="' + link + '.' + f + '">' + this.translate(f, 'fields', linkDefs[link].entity) + '</option>';
                    if (i == relatedFields[link].length - 1) {
                        listHtml += '</optgroup>';
                    }
                }, this);
            }

            this.listHtml = listHtml;
        },

    });
});

