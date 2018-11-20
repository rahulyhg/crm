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

Core.define('Advanced:Views.Report.List', 'Views.List', function (Dep) {

    return Dep.extend({

        createButton: false,

        actionCreate: function (data) {
            this.createView('createModal', 'Advanced:Report.Modals.Create', {}, function (view) {
                view.render();

                this.listenToOnce(view, 'create', function (data) {
                    view.close();
                    this.getRouter().dispatch('Report', 'create', {
                        entityType: data.entityType,
                        type: data.type
                    });
                    this.getRouter().navigate('#Report/create/entityType=' + data.entityType + '&type=' + data.type, {trigger: false});
                }, this);

            }.bind(this));

        }

    });
});
