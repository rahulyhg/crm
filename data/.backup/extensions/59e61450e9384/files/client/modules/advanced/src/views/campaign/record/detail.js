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

Core.define('Advanced:Views.Campaign.Record.Detail', 'Crm:Views.Campaign.Record.Detail', function (Dep) {

    return Dep.extend({

        getMailChimpButton: function () {
            return this.$el.parent().find(".header-buttons .btn[data-name='mailChimpButton']");
        },

        handleMailChimpButtonVisibility: function () {
            if (this.model.get('type') == 'Email' || this.model.get('type') == 'Newsletter') {
                this.getMailChimpButton().removeClass('hidden');
            } else {
                this.getMailChimpButton().addClass('hidden');
            }
        },

        handleMailChimpButtonStyle: function () {
            if (this.model.get('mailChimpCampaignId') == null) {
                this.getMailChimpButton().addClass('btn-danger');
            } else {
                this.getMailChimpButton().removeClass('btn-danger');
            }
        },

        afterRender: function () {
        	Dep.prototype.afterRender.call(this);
            this.handleMailChimpButtonVisibility();
            this.listenTo(this.model, 'sync', function () {
                this.handleMailChimpButtonVisibility();
            }, this);
            this.handleMailChimpButtonStyle();
            this.listenTo(this.model, 'sync', function () {
                this.handleMailChimpButtonStyle();
            }, this);
        },

    });
});
