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

Core.define('Advanced:Views.MailChimp.Notification', 'Views.PopupNotification', function (Dep) {

    return Dep.extend({

        type: 'event',

        style: 'primary',

        template: 'advanced:mail-chimp.notification',

        closeButton: true,

        setup: function () {
            this.wait(true);

            if (this.notificationData.entityType) {
                this.getModelFactory().create(this.notificationData.entityType, function (model) {

                    model.set('lastSynced', this.notificationData.lastSynced);

                    this.createView('lastSynced', 'Fields.Datetime', {
                        model: model,
                        mode: 'detail',
                        el: this.options.el + ' .field-lastSynced',
                        defs: {
                            name: 'lastSynced'
                        },
                        readOnly: true
                    });

                    this.wait(false);
                }, this);
            }
        },

        data: function () {
            return _.extend({
                header: this.translate(this.notificationData.entityType, 'scopeNames')
            }, Dep.prototype.data.call(this));
        },
    });
});

