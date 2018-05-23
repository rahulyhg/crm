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

Core.define('Advanced:Views.Report.Fields.GroupBy', 'Views.Fields.MultiEnum', function (Dep) {

    return Dep.extend({

        validations: ['required', 'maxCount'],

        validateMaxCount: function () {
            var items = this.model.get(this.name) || [];
            var maxCount = 2;
            if (items.length > maxCount) {
                var msg = this.translate('validateMaxCount', 'messages', 'Report').replace('{field}', this.translate(this.name, 'fields', this.model.name))
                                                                                  .replace('{maxCount}', maxCount);
                this.showValidationMessage(msg);
                return true;
            }
        },

        setupOptions: function () {
            var entityType = this.model.get('entityType');

            var fields = this.getMetadata().get('entityDefs.' + entityType + '.fields') || {};

            var itemList = [];

            var fieldList = Object.keys(fields);

            fieldList.sort(function (v1, v2) {
                return this.translate(v1, 'fields', entityType).localeCompare(this.translate(v2, 'fields', entityType));
            }.bind(this));

            fieldList.forEach(function (field) {
                if (fields[field].disabled) return;
                if (fields[field].reportDisabled) return;
                if (fields[field].reportGroupByDisabled) return;

                if (~['date', 'datetime'].indexOf(fields[field].type)) {
                    itemList.push('MONTH:' + field);
                    itemList.push('YEAR:' + field);
                    itemList.push('DAY:' + field);
                }
            }, this);

            fieldList.forEach(function (field) {
                if (
                    ~[
                        'linkMultiple',
                        'date',
                        'datetime',
                        'currency',
                        'currencyConverted',
                        'text',
                        'map',
                        'multiEnum',
                        'array'
                    ].indexOf(fields[field].type)
                ) return;
                if (fields[field].disabled) return;
                if (fields[field].disabled) return;
                if (fields[field].reportDisabled) return;
                if (fields[field].reportGroupByDisabled) return;

                itemList.push(field);
            }, this);

            var links = this.getMetadata().get('entityDefs.' + entityType + '.links') || {};

            var linkList = Object.keys(links);

            linkList.sort(function (v1, v2) {
                return this.translate(v1, 'links', entityType).localeCompare(this.translate(v2, 'links', entityType));
            }.bind(this));

            linkList.forEach(function (link) {
                if (links[link].type != 'belongsTo') return;
                var scope = links[link].entity;
                if (!scope) return;

                var fields = this.getMetadata().get('entityDefs.' + scope + '.fields') || {};
                var fieldList = Object.keys(fields);

                fieldList.sort(function (v1, v2) {
                    return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
                }.bind(this));

                fieldList.forEach(function (field) {
                    if (fields[field].disabled) return;
                    if (~['date', 'datetime'].indexOf(fields[field].type)) {
                        itemList.push('MONTH:' + link + '.' + field);
                        itemList.push('YEAR:' + link + '.' +  field);
                        itemList.push('DAY:' + link + '.' + field);
                    }
                    if (~['linkMultiple', 'linkParent', 'phone', 'email', 'date', 'datetime', 'currency', 'currencyConverted', 'text', 'personName'].indexOf(fields[field].type)) return;
                    itemList.push(link + '.' + field);
                }, this);
            }, this);

            this.params.options = itemList;

        },

        setupTranslatedOptions: function () {
            this.translatedOptions = {};

            var entityType = this.model.get('entityType');
            this.params.options.forEach(function (item) {
                var hasFunction = false;
                var field = item;
                var scope = entityType;
                var isForeign = false;
                var p = item;
                var link = null
                var func = null;

                if (~item.indexOf(':')) {
                    hasFunction = true;
                    func = item.split(':')[0];
                    p = field = item.split(':')[1];
                }

                if (~p.indexOf('.')) {
                    isForeign = true;
                    link = p.split('.')[0];
                    field = p.split('.')[1];
                    scope = this.getMetadata().get('entityDefs.' + entityType + '.links.' + link + '.entity');
                }
                this.translatedOptions[item] = this.translate(field, 'fields', scope);
                if (isForeign) {
                    this.translatedOptions[item] = this.translate(link, 'links', entityType) + '.' + this.translatedOptions[item];
                }
                if (hasFunction) {
                    this.translatedOptions[item] = this.translate(func, 'functions', 'Report').toUpperCase() + ': ' + this.translatedOptions[item];
                }
            }, this);
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.setupOptions();
            this.setupTranslatedOptions();
        },

    });

});

