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

Core.define('Advanced:Views.Workflow.Actions.CreateNotification', ['Advanced:Views.Workflow.Actions.Base', 'Model'], function (Dep, Model) {

    return Dep.extend({

        template: 'advanced:workflow.actions.create-notification',

        type: 'createNotification',

        defaultActionData: {
            recipient: 'specifiedUsers',
            userIdList: [],
            userNames: {}
        },

        data: function () {
            var data = Dep.prototype.data.call(this);
            data.recipientLabel = this.translateRecipientOption(this.actionData.recipient);
            data.messageTemplate = this.actionData.messageTemplate;
            return data;
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            var model = new Model();
            model.name = 'Workflow';
            model.set({
                recipient: this.actionData.recipient,
                messageTemplate: this.actionData.messageTemplate,
                usersIds: this.actionData.userIdList,
                usersNames: this.actionData.userNames,
                specifiedTeamsIds: this.actionData.specifiedTeamsIds,
                specifiedTeamsNames: this.actionData.specifiedTeamsNames
            });

            if (this.actionData.recipient === 'specifiedUsers') {
                this.createView('users', 'Fields.LinkMultiple', {
                    mode: 'detail',
                    model: model,
                    el: this.options.el + ' .field-recipient',
                    foreignScope: 'User',
                    defs: {
                        name: 'users'
                    },
                    readOnly: true
                }, function (view) {
                    view.render();
                });
            }

            if (this.actionData.recipient === 'specifiedTeams') {
                this.createView('specifiedTeams', 'Fields.LinkMultiple', {
                    mode: 'detail',
                    model: model,
                    el: this.options.el + ' .field-recipient',
                    foreignScope: 'Team',
                    defs: {
                        name: 'specifiedTeams'
                    },
                    readOnly: true
                }, function (view) {
                    view.render();
                });
            }
        },

        translateRecipientOption: function (value) {
            var linkDefs = this.getMetadata().get('entityDefs.' + this.entityType + '.links.' + value);
            if (linkDefs) {
                return this.translate(value, 'links' , this.entityType);
            }

            return this.translate(value, 'emailAddressOptions', 'Workflow');
        }

    });
});

