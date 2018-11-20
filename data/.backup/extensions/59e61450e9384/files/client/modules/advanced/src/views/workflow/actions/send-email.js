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

Core.define('Advanced:Views.Workflow.Actions.SendEmail', ['Advanced:Views.Workflow.Actions.Base', 'Model'], function (Dep, Model) {

    return Dep.extend({

        template: 'advanced:workflow.actions.send-email',

        type: 'sendEmail',

        defaultActionData: {
            execution: {
                type: 'immediately',
                field: false,
                shiftDays: 0,
                shiftUnit: 'days'
            },
            from: 'currentUser',
            to: ''
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.createView('executionTime', 'Advanced:Workflow.ActionFields.ExecutionTime', {
                el: this.options.el + ' .execution-time-container',
                executionData: this.actionData.execution || {},
                entityType: this.entityType,
                readOnly: true
            });

            var model = new Model();
            model.name = 'Workflow';
            model.set({
                emailTemplateId: this.actionData.emailTemplateId,
                emailTemplateName: this.actionData.emailTemplateName,
                doNotStore: this.actionData.doNotStore
            });

            this.createView('emailTemplate', 'Fields.Link', {
                el: this.options.el + ' .field-emailTemplate',
                model: model,
                mode: 'edit',
                foreignScope: 'EmailTemplate',
                defs: {
                    name: 'emailTemplate',
                    params: {
                        required: true
                    }
                },
                readOnly: true
            });

            this.createView('toSpecifiedTeams', 'Fields.LinkMultiple', {
                el: this.options.el + ' .toSpecifiedTeams-container .field-toSpecifiedTeams',
                model: model,
                mode: 'edit',
                foreignScope: 'Team',
                defs: {
                    name: 'toSpecifiedTeams'
                },
                readOnly: true
            });

            this.createView('toSpecifiedUsers', 'Fields.LinkMultiple', {
                el: this.options.el + ' .toSpecifiedUsers-container .field-toSpecifiedUsers',
                model: model,
                mode: 'edit',
                foreignScope: 'User',
                defs: {
                    name: 'toSpecifiedUsers'
                },
                readOnly: true
            });

            this.createView('toSpecifiedContacts', 'Fields.LinkMultiple', {
                el: this.options.el + ' .toSpecifiedContacts-container .field-toSpecifiedContacts',
                model: model,
                mode: 'edit',
                foreignScope: 'Contact',
                defs: {
                    name: 'toSpecifiedContacts'
                },
                readOnly: true
            });

            this.createView('doNotStore', 'Fields.Bool', {
                el: this.options.el + ' .field-doNotStore',
                model: model,
                mode: 'edit',
                defs: {
                    name: 'doNotStore'
                },
                readOnly: true
            });
        },

        render: function (callback) {
            this.getView('executionTime').reRender();

            var emailTemplateView = this.getView('emailTemplate');
            emailTemplateView.model.set({
                emailTemplateId: this.actionData.emailTemplateId,
                emailTemplateName: this.actionData.emailTemplateName
            });
            emailTemplateView.reRender();

            if (this.actionData.toSpecifiedEntityIds) {
                var viewName = 'to' + this.actionData.to.charAt(0).toUpperCase() + this.actionData.to.slice(1);
                var toSpecifiedEntityView = this.getView(viewName);
                if (toSpecifiedEntityView) {
                    var toSpecifiedEntityData = {};
                    toSpecifiedEntityData[viewName + 'Ids'] = this.actionData.toSpecifiedEntityIds;
                    toSpecifiedEntityData[viewName + 'Names'] = this.actionData.toSpecifiedEntityNames;

                    toSpecifiedEntityView.model.set(toSpecifiedEntityData);
                    toSpecifiedEntityView.reRender();
                }
            }

            //translate To and From option
            if (this.actionData.from) {
                this.actionData.fromLabel = this.translateEmailOption(this.actionData.from);
            }
            if (this.actionData.to) {
                this.actionData.toLabel = this.translateEmailOption(this.actionData.to);
            }

            var doNotStore = this.getView('doNotStore');
            doNotStore.model.set({
                doNotStore: this.actionData.doNotStore
            });
            doNotStore.reRender();

            Dep.prototype.render.call(this, callback);
        },

        renderFields: function () {
        },

        translateEmailOption: function (value) {
            var linkDefs = this.getMetadata().get('entityDefs.' + this.entityType + '.links.' + value);
            if (linkDefs) {
                return this.translate(value, 'links' , this.entityType);
            }

            var label = this.translate(value, 'emailAddressOptions', 'Workflow');
            if (value == 'targetEntity') {
                label += ' (' + this.entityType + ')';
            }

            return label;
        }

    });
});

