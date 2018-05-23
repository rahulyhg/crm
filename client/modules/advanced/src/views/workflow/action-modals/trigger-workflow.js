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

Core.define('Advanced:Views.Workflow.ActionModals.TriggerWorkflow', ['Advanced:Views.Workflow.ActionModals.Base', 'Model'], function (Dep, Model) {

    return Dep.extend({

        template: 'advanced:workflow.action-modals.trigger-workflow',

        data: function () {
            return _.extend({
            }, Dep.prototype.data.call(this));
        },


        afterRender: function () {
            Dep.prototype.afterRender.call(this);
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.createView('executionTime', 'Advanced:Workflow.ActionFields.ExecutionTime', {
                el: this.options.el + ' .execution-time-container',
                executionData: this.actionData.execution || {},
                entityType: this.entityType
            });

            var model = new Model();

            model.name = 'Workflow';

            model.set({
                workflowId: this.actionData.workflowId,
                workflowName: this.actionData.workflowName
            });

            this.createView('workflow', 'advanced:views/workflow/fields/workflow', {
                el: this.options.el + ' .field-workflow',
                model: model,
                mode: 'edit',
                foreignScope: 'Workflow',
                entityType: this.entityType,
                defs: {
                    name: 'workflow',
                    params: {
                        required: true
                    }
                }
            });
        },

        fetch: function () {
            var workflowView = this.getView('workflow');
            workflowView.fetchToModel();
            if (workflowView.validate()) {
                return;
            }
            var o = workflowView.fetch();
            this.actionData.workflowId = o.workflowId;
            this.actionData.workflowName = o.workflowName;

            this.actionData.execution = this.actionData.execution || {};
            this.actionData.execution.type = this.$el.find('[name="executionType"]').val();

            if (this.actionData.execution.type != 'immediately') {
                this.actionData.execution.field = this.$el.find('[name="executionField"]').val();
                this.actionData.execution.shiftDays = this.$el.find('[name="shiftDays"]').val();
                this.actionData.execution.shiftUnit = this.$el.find('[name="shiftUnit"]').val();

                if (this.$el.find('[name="shiftDaysOperator"]').val() == 'minus') {
                    this.actionData.execution.shiftDays = (-1) * this.actionData.execution.shiftDays;
                }
            }

            return true;
        }

    });
});
