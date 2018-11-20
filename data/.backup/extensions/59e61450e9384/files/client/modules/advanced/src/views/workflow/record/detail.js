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

Core.define('advanced:views/workflow/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        editModeEnabled: false,

        editModeDisabled: true,

        bottomView: 'advanced:views/workflow/record/detail-bottom',

        duplicateAction: true,

        setup: function () {
            Dep.prototype.setup.call(this);
            this.manageFieldsVisibility();
            this.listenTo(this.model, 'change', function (model, options) {
                if (this.model.hasChanged('portalOnly') || this.model.hasChanged('type')) {
                    this.manageFieldsVisibility(options.ui);
                }
            }, this);

            if (!this.model.isNew()) {
                this.setFieldReadOnly('type');
                this.setFieldReadOnly('entityType');
            }
        },

        manageFieldsVisibility: function (ui) {
            if (this.model.get('portalOnly') && ~['afterRecordSaved', 'afterRecordCreated'].indexOf(this.model.get('type'))) {
                this.showField('portal');
            } else {
                this.hideField('portal');
            }

            if (this.model.get('type') === 'scheduled') {
                this.showField('targetReport');
                this.showField('scheduling');
                this.setFieldRequired('targetReport');
                this.hideField('portal');
                this.hideField('portalOnly');
                if (this.mode === 'edit' && ui) {
                    setTimeout(function () {
                        this.model.set({
                            'portalId': null,
                            'portalName': null,
                            'portalOnly': false
                        });
                    }.bind(this), 100);
                }
            } else {
                this.hideField('targetReport');
                this.hideField('scheduling');
                this.setFieldNotRequired('targetReport');

                if (this.model.get('type') === 'sequential') {
                    this.hideField('portal');
                    this.hideField('portalOnly');
                    if (this.mode === 'edit' && ui) {
                        setTimeout(function () {
                            this.model.set({
                                'portalId': null,
                                'portalName': null,
                                'portalOnly': false
                            });
                        }.bind(this), 100);
                    }
                } else {
                    if (this.model.get('portalOnly')) {
                        this.showField('portal');
                    }
                    this.showField('portalOnly');
                }
            }
        }

    });
});


