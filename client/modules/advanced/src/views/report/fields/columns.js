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

Core.define('Advanced:Views.Report.Fields.Columns', ['Views.Fields.MultiEnum', 'Advanced:Views.Report.Fields.GroupBy'], function (Dep, GroupBy) {

    return Dep.extend({

        setupOptions: function () {
            var entityType = this.model.get('entityType');

            var fields = this.getMetadata().get('entityDefs.' + entityType + '.fields') || {};

            var itemList = [];

            itemList.push('COUNT:id');

            Object.keys(fields).forEach(function (field) {
                if (fields[field].disabled) return;
                if (~['currencyConverted', 'int', 'float', 'duration'].indexOf(fields[field].type)) {
                    itemList.push('SUM:' + field);
                    itemList.push('MAX:' + field);
                    itemList.push('MIN:' + field);
                    itemList.push('AVG:' + field);
                }
            }, this);

            this.params.options = itemList;
        },

        setupTranslatedOptions: function () {
            GroupBy.prototype.setupTranslatedOptions.call(this);

            this.params.options.forEach(function (item) {
                if (item == 'COUNT:id') {
                    this.translatedOptions[item] = this.translate('COUNT', 'functions', 'Report').toUpperCase();
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

