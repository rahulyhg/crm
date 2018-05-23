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

Core.define('advanced:views/report/record/export-grid', 'views/record/base', function (Dep) {

    return Dep.extend({

        template: 'advanced:report/record/export-grid',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.scope = this.options.scope;

            var gridReportFormatList = this.getMetadata().get('app.export.gridReportFormatList') || [];

            var version = this.getConfig().get('version') || '';
            var arr = version.split('.');
            if (version !== 'dev' && arr.length > 2 && parseInt(arr[0]) * 100 + parseInt(arr[1]) < 407) {
                gridReportFormatList = ['csv'];
            }

            this.createField('exportFormat', 'views/fields/enum', {
                options: gridReportFormatList
            });

            this.controlColumnField();
            this.listenTo(this.model, 'change:exportFormat', this.controlColumnField, this);

            if (this.options.columnList) {
                this.createField('column', 'views/fields/enum', {
                    options: this.options.columnList,
                    translatedOptions: this.options.columnsTranslation || {}
                });
            }
        },

        controlColumnField: function () {
            if (this.model.get('exportFormat') === 'csv') {
                this.showField('column');
            } else {
                this.hideField('column');
            }
        }

    });

});

