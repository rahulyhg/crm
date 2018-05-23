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


Core.define('advanced:views/report/modals/export-grid', ['views/modal', 'model'], function (Dep, Model) {

    return Dep.extend({

        _template: '<div class="record">{{{record}}}</div>',

        setup: function () {
            this.buttonList = [
                {
                    name: 'export',
                    label: 'Export',
                    style: 'danger'
                },
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];

            this.model = new Model();
            this.model.name = 'Report';

            this.scope = this.options.scope;

            var exportFormat = (this.getMetadata().get('app.export.gridReportFormatList') || [])[0];

            this.model.set('exportFormat', exportFormat);

            this.createView('record', 'advanced:views/report/record/export-grid', {
                scope: this.scope,
                model: this.model,
                el: this.getSelector() + ' .record',
                columnList: this.options.columnList,
                columnsTranslation: this.options.columnsTranslation
            });
        },

        actionExport: function () {
            var data = this.getView('record').fetch();
            this.model.set(data);
            if (this.getView('record').validate()) return;

            var returnData = {
                format: data.exportFormat,
                column: data.column
            };

            this.trigger('proceed', returnData);
            this.close();
        }

    });
});

