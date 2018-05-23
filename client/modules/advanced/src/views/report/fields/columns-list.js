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

Core.define('Advanced:Views.Report.Fields.ColumnsList', 'Views.Fields.MultiEnum', function (Dep) {

    return Dep.extend({

        setupOptions: function () {
            var entityType = this.model.get('entityType');

            var fields = this.getMetadata().get('entityDefs.' + entityType + '.fields') || {};

            var itemList = [];

            Object.keys(fields).forEach(function (field) {
                if (fields[field].disabled) return;
                if (fields[field].type == 'linkMultiple') return;
                if (fields[field].type == 'map') return;

                if (fields[field].reportDisabled) return;
                if (fields[field].reportColumnDisabled) return;

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
                    if (~['link', 'linkMultiple', 'linkParent', 'email', 'phone', 'currency', 'currencyConverted', 'personName', 'map'].indexOf(fields[field].type)) return;
                    itemList.push(link + '.' + field);
                }, this);
            }, this);

            this.params.options = itemList;
        },

        setupTranslatedOptions: function () {
            this.translatedOptions = {};

            var entityType = this.model.get('entityType');

            this.params.options.forEach(function (item) {
                var field = item;
                var scope = entityType;
                var isForeign = false;
                var p = item;
                var link = null
                var func = null;

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
            }, this);
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.setupOptions();
            this.setupTranslatedOptions();
        }

    });

});

