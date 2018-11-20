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

Core.define('Advanced:Views.Report.Record.Edit', ['Views.Record.Edit', 'Advanced:Views.Report.Record.Detail'], function (Dep, Detail) {

    return Dep.extend({

        setup: function () {
            if (!this.model.get('type')) {
                throw new Error();
            }
            if (this.model.get('isInternal')) {
                this.layoutName = 'detail';
            } else {
                this.layoutName = 'detail' + this.model.get('type');
            }

            if (this.model.get('type') == 'List' && this.model.isNew() && !this.model.has('columns')) {
                if (this.getMetadata().get('entityDefs.' + this.model.get('entityType') + '.fields.name')) {
                    this.model.set('columns', ['name']);
                }
            }

            Dep.prototype.setup.call(this);

            this.controlChartColorsVisibility();
            this.listenTo(this.model, 'change', function () {
                if (this.model.hasChanged('chartType') || this.model.hasChanged('groupBy')) {
                    this.controlChartColorsVisibility();
                }
            }, this);

        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.handleDoNotSendEmptyReportVisibility();
            this.listenTo(this.model, 'change:emailSendingInterval', function () {
                this.handleDoNotSendEmptyReportVisibility();
            }, this);
        },

        handleDoNotSendEmptyReportVisibility: function() {
            var fieldName = "emailSendingDoNotSendEmptyReport";
            if (this.model.get('type') == 'List') {
                if (this.model.get("emailSendingInterval") == "") {
                    this.hideField(fieldName);
                } else {
                    this.showField(fieldName);
                }
            }  else {
                this.hideField(fieldName);
            }
        },

        controlChartColorsVisibility: function () {
            var chartType = this.model.get('chartType');

            if (!chartType || chartType === '') {
                this.hideField('chartColor');
                this.hideField('chartColorList');
                return;
            }

            if ((this.model.get('groupBy') || []).length > 1) {
                this.hideField('chartColor');
                this.showField('chartColorList');
                return;
            }

            if (chartType === 'Pie') {
                this.hideField('chartColor');
                this.showField('chartColorList');
                return;
            }

            this.showField('chartColor');
            this.hideField('chartColorList');
        }

    });

});

