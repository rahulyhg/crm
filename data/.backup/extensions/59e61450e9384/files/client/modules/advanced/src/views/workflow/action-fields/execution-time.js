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

Core.define('Advanced:Views.Workflow.ActionFields.ExecutionTime', 'View', function (Dep) {

    return Dep.extend({

        template: 'advanced:workflow.action-fields.execution-time',

        events: {
            'change [name="executionType"]': function (e) {
                this.executionData.type = e.currentTarget.value;
                this.handleType();
            },
        },

        data: function () {
            return {
                type: this.executionData.type,
                field: this.executionData.field,
                shiftDays: this.executionData.shiftDays,
                shiftUnit: this.executionData.shiftUnit,
                readOnly: this.readOnly
            };
        },

        setup: function () {
            this.executionData = this.options.executionData || {};
            this.readOnly = this.options.readOnly || false;

            this.createFieldView();
            this.createShiftDaysView();
        },

        afterRender: function () {
            this.handleType();
        },

        reRender: function () {
            this.createFieldView();
            this.createShiftDaysView();

            Dep.prototype.reRender.call(this);
        },

        handleType: function () {
            if (this.executionData.type != 'later') {
                this.$el.find('.field-container').addClass('hidden');
                this.$el.find('.shift-days-container').addClass('hidden');
            } else {
                this.$el.find('.field-container').removeClass('hidden');
                this.$el.find('.shift-days-container').removeClass('hidden');
            }
        },

        createFieldView: function () {
            this.createView('field', 'Advanced:Workflow.ActionFields.DateField', {
                el: this.options.el + ' .field-container',
                value: this.executionData.field,
                entityType: this.options.entityType,
                readOnly: this.readOnly
            });
        },

        createShiftDaysView: function () {
            this.createView('shiftDays', 'Advanced:Workflow.ActionFields.ShiftDays', {
                el: this.options.el + ' .shift-days-container',
                value: this.executionData.shiftDays || 0,
                unitValue: this.executionData.shiftUnit || 'days',
                readOnly: this.readOnly
            });
        }

    });
});

