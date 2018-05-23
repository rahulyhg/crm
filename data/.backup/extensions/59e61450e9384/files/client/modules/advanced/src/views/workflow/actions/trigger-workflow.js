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

Core.define('Advanced:Views.Workflow.Actions.TriggerWorkflow', ['Advanced:Views.Workflow.Actions.Base', 'Model'], function (Dep, Model) {

    return Dep.extend({

        template: 'advanced:workflow.actions.trigger-workflow',

        type: 'triggerWorkflow',

        defaultActionData: {
            execution: {
                type: 'immediately',
                field: false,
                shiftDays: 0,
                shiftUnit: 'days'
            }
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
                workflowId: this.actionData.workflowId,
                workflowName: this.actionData.workflowName
            });

            this.createView('workflow', 'views/fields/link', {
                el: this.options.el + ' .field-workflow',
                model: model,
                mode: 'edit',
                foreignScope: 'Workflow',
                defs: {
                    name: 'workflow',
                    params: {
                        required: true
                    }
                },
                readOnly: true
            });
        },

        render: function (callback) {
            this.getView('executionTime').reRender();

            var workflowView = this.getView('workflow');
            workflowView.model.set({
                workflowId: this.actionData.workflowId,
                workflowName: this.actionData.workflowName
            });
            workflowView.reRender();

            Dep.prototype.render.call(this, callback);
        },



    });
});

