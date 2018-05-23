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

Core.define('Advanced:Views.Report.Record.Detail', 'Views.Record.Detail', function (Dep) {

    return Dep.extend({

        editModeDisabled: true,

        duplicateAction: true,

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
        }

    });

});
