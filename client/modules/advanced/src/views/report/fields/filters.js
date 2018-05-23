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

Core.define('Advanced:Views.Report.Fields.Filters', 'Views.Fields.MultiEnum', function (Dep) {

    return Dep.extend({

        getFilterList: function () {
            var entityType = this.model.get('entityType');

            var fields = this.getMetadata().get('entityDefs.' + entityType + '.fields');
            var filterList = Object.keys(fields).filter(function (field) {
                if (this.options.skipLinkMultiple) {
                    if (fields[field].type === 'linkMultiple') return;
                }

                if (fields[field].type === 'map') return;

                if (fields[field].disabled) return;
                if (fields[field].reportDisabled) return;
                if (fields[field].reportFilterDisabled) return;

                return this.getFieldManager().checkFilter(fields[field].type);
            }, this);

            filterList.sort(function (v1, v2) {
                return this.translate(v1, 'fields', entityType).localeCompare(this.translate(v2, 'fields', entityType));
            }.bind(this));

            var links = this.getMetadata().get('entityDefs.' + entityType + '.links') || {};

            var linkList = Object.keys(links).sort(function (v1, v2) {
                return this.translate(v1, 'links', entityType).localeCompare(this.translate(v2, 'links', entityType));
            }.bind(this));

            linkList.forEach(function (link) {
                var type = links[link].type
                if (type != 'belongsTo' && type != 'hasMany' && type != 'hasChildren') return;
                var scope = links[link].entity;
                if (!scope) return;

                var fields = this.getMetadata().get('entityDefs.' + scope + '.fields') || {};
                var foreignFilterList = Object.keys(fields).filter(function (field) {
                    if (~['linkMultiple', 'linkParent', 'personName'].indexOf(fields[field].type)) return;
                    if (fields[field].where) return;

                    return this.getFieldManager().checkFilter(fields[field].type) && !fields[field].disabled;
                }, this);
                foreignFilterList.sort(function (v1, v2) {
                    return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
                }.bind(this));

                foreignFilterList.forEach(function (item) {
                    filterList.push(link + '.' + item);
                }, this);
            }, this);

            return filterList;
        },

        setupTranslatedOptions: function () {
            this.translatedOptions = {};

            var entityType = this.model.get('entityType');
            this.params.options.forEach(function (item) {
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

        setup: function () {
            Dep.prototype.setup.call(this);

            this.params.options = this.getFilterList();
            this.setupTranslatedOptions();
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (this.$element && this.$element[0] && this.$element[0].selectize) {
                this.$element[0].selectize.focus();
            }
        }

    });

});

