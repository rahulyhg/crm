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

Core.define('advanced:views/target-list/detail', 'views/detail', function (Dep) {

    return Dep.extend({

    setup: function () {
            Dep.prototype.setup.call(this);

            var isDisabled = this.getMetadata().get('app.popupNotifications.mailChimpNotification.disabled') || false;
            if (!isDisabled && this.getAcl().check('MailChimp')) {
                var mailChimpButton = {
                        label: "MailChimp Sync",
                        action: "showModal",
                        data: {
                            view: "Advanced:TargetList.Modals.MailChimp",
                            name: "mailChimpButton"
                        }
                    };
                this.menu.buttons[this.menu.buttons.length] = mailChimpButton;
            }
        },

    });
});


