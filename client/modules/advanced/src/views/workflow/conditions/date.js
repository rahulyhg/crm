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

Core.define('Advanced:Views.Workflow.Conditions.Date', 'Advanced:Views.Workflow.Conditions.Base', function (Dep) {

    return Dep.extend({

        template: 'advanced:workflow.conditions.date',

        comparisonList: [
            'on',
            'before',
            'after',
            'today',
            'beforeToday',
            'afterToday',
            'changed',
            'notEmpty'
        ],

        defaultConditionData: {
            comparison: 'on',
            subjectType: 'today',
            shiftDays: 0,
        },

        events: _.extend({
            'change [name="shiftDays"]': function (e) {
                this.setShiftDays(e.currentTarget.value);
                this.handleShiftDays(e.currentTarget.value);
            },
        }, Dep.prototype.events),

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.handleShiftDays(this.conditionData.shiftDays, true);
        },

        handleComparison: function (comparison, noFetch) {
            Dep.prototype.handleComparison.call(this, comparison, noFetch);

            switch (comparison) {
                case 'on':
                case 'before':
                case 'after':
                    this.$el.find('.subject').empty();

                    this.createView('subjectType', 'Advanced:Workflow.ConditionFields.SubjectTypeDate', {
                        el: this.options.el + ' .subject-type',
                        value: this.conditionData.subjectType,
                        readOnly: this.readOnly
                    }, function (view) {
                        view.render(function() {
                            if (!noFetch) {
                                this.fetch();
                            }
                            this.handleSubjectType(this.conditionData.subjectType);
                        }.bind(this));
                    }.bind(this));

                    this.createView('shiftDays', 'Advanced:Workflow.ConditionFields.ShiftDays', {
                        el: this.options.el + ' .shift-days',
                        entityType: this.entityType,
                        field: this.field,
                        value: this.conditionData.shiftDays || 0,
                        readOnly: this.readOnly
                    }, function (view) {
                        view.render(function () {
                            if (!noFetch) {
                                this.fetch();
                                this.handleShiftDays(this.conditionData.subject);
                            }
                        }.bind(this));
                    }.bind(this));

                    break;
                default:
                    this.$el.find('.shift-days').empty();
            }
        },

        setShiftDays: function (shiftDays) {
            this.conditionData.shiftDays = shiftDays;
        },

        fetch: function () {
            Dep.prototype.fetch.call(this);
            this.fetchShiftDays();
            return this.conditionData;
        },

        fetchShiftDays: function () {
            var $shiftDays = this.$el.find('[name="shiftDays"]');
            if ($shiftDays.size()) {
                this.conditionData.shiftDays = parseInt($shiftDays.val()) || 0;

                var $shiftDaysOperator = this.$el.find('[name="shiftDaysOperator"]');
                if ($shiftDaysOperator.val() == 'minus') {
                    this.conditionData.shiftDays = (-1) * this.conditionData.shiftDays;
                }
            }
        },

        handleShiftDays: function (shiftDays, noFetch) {
            if (!noFetch) {
                this.fetch();
            }
        },

    });
});
