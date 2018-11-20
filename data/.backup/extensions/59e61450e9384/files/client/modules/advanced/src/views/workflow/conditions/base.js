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

Core.define('Advanced:Views.Workflow.Conditions.Base', 'View', function (Dep) {

    return Dep.extend({

        template: 'advanced:workflow.conditions.base',

        defaultConditionData: {
            comparison: 'equals',
            subjectType: 'value'
        },

        comparisonList: [
            'equals',
            'wasEqual',
            'notEquals',
            'wasNotEqual',
            'changed',
            'isNotEmpty'
        ],

        events: {
            'change [name="comparison"]': function (e) {
                this.setComparison(e.currentTarget.value);
                this.handleComparison(e.currentTarget.value);
            },
            'change [name="subjectType"]': function (e) {
                this.setSubjectType(e.currentTarget.value);
                this.handleSubjectType(e.currentTarget.value);
            },
            'change [name="subject"]': function (e) {
                this.setSubject(e.currentTarget.value);
                this.handleSubject(e.currentTarget.value);
            },
        },

        data: function () {
            return {
                field: this.field,
                entityType: this.entityType,
                comparisonValue: this.conditionData.comparison,
                comparisonList: this.comparisonList,
                readOnly: this.readOnly
            };
        },

        setup: function () {
            this.conditionType = this.options.conditionType;

            this.conditionData = this.options.conditionData || {};

            if (this.options.isNew) {
                var cloned = {};
                for (var i in this.defaultConditionData) {
                    cloned[i] = Core.Utils.clone(this.defaultConditionData[i]);
                }
                this.conditionData = _.extend(cloned, this.conditionData);
            }

            this.field = this.options.field;

            this.conditionData.fieldToCompare = this.field;

            this.entityType = this.options.entityType;
            this.type = this.options.type;
            this.fieldType = this.options.fieldType;
            this.readOnly = this.options.readOnly;
        },

        afterRender: function () {
            this.handleComparison(this.conditionData.comparison, true);

            this.$comparison = this.$el.find('[name="comparison"]');
        },

        fetchComparison: function () {
            var $comparison = this.$el.find('[name="comparison"]');
            if ($comparison.size()) {
                this.conditionData.comparison = $comparison.val();
            }
        },

        fetchSubjectType: function () {
            var $subjectType = this.$el.find('[name="subjectType"]');
            if ($subjectType.size()) {
                this.conditionData.subjectType = $subjectType.val();
            }
        },

        fetchSubject: function () {
            delete this.conditionData.value;
            delete this.conditionData.field;

            if ('fetch' in (this.getView('subject') || {})) {
                var data = this.getView('subject').fetch() || {};
                for (var attr in data) {
                    this.conditionData[attr] = data[attr];
                }
                return;
            }

            var $subject = this.$el.find('[name="subject"]');
            if ($subject.size()) {
                switch (this.conditionData.subjectType) {
                    case 'field':
                        this.conditionData.field = $subject.val();
                        break;
                    case 'value':
                        this.conditionData.value = $subject.val();
                        break;
                }

            }
        },

        fetch: function () {
            this.fetchComparison();
            this.fetchSubjectType();
            this.fetchSubject();

            return this.conditionData;
        },

        setComparison: function (comparison) {
            this.conditionData.comparison = comparison;
        },

        setSubjectType: function (subjectType) {
            this.conditionData.subjectType = subjectType;
        },

        setSubject: function (subject) {
            this.conditionData.subject = subject;
        },

        handleComparison: function (comparison, noFetch) {
            switch (comparison) {
                case 'changed':
                case 'notEmpty':
                case 'isEmpty':
                case 'empty':
                case 'true':
                case 'false':
                case 'today':
                case 'beforeToday':
                case 'afterToday':
                    this.$el.find('.subject-type').empty();
                    this.$el.find('.subject').empty();
                    break;
                case 'equals':
                case 'wasEqual':
                case 'notEquals':
                case 'wasNotEqual':
                case 'greaterThan':
                case 'lessThan':
                case 'greaterThanOrEquals':
                case 'lessThanOrEquals':
                case 'has':
                case 'notHas':
                    this.createView('subjectType', 'Advanced:Workflow.ConditionFields.SubjectType', {
                        el: this.options.el + ' .subject-type',
                        value: this.conditionData.subjectType,
                        readOnly: this.readOnly
                    }, function (view) {
                        view.render(function() {
                            if (!noFetch) {
                                this.fetch();
                            }
                            this.handleSubjectType(this.conditionData.subjectType, noFetch);
                        }.bind(this));
                    }.bind(this));
                    break;
            }
        },

        getSubjectInputViewName: function (subjectType) {
            return 'Advanced:Workflow.ConditionFields.Subjects.TextInput';
        },

        handleSubjectType: function (subjectType, noFetch) {
            switch (subjectType) {
                case 'value':
                    this.createView('subject', this.getSubjectInputViewName(subjectType), {
                        el: this.options.el + ' .subject',
                        entityType: this.entityType,
                        field: this.field,
                        value: this.getSubjectValue(),
                        conditionData: this.conditionData,
                        readOnly: this.readOnly
                    }, function (view) {
                        view.render(function () {
                            if (!noFetch) {
                                this.fetch();
                            }
                            this.handleSubject(this.conditionData.subject, noFetch);
                        }.bind(this));
                    }.bind(this));
                    break;
                case 'field':
                    this.createView('subject', 'Advanced:Workflow.ConditionFields.Subjects.Field', {
                        el: this.options.el + ' .subject',
                        entityType: this.entityType,
                        value: this.conditionData.field,
                        fieldType: this.fieldType,
                        field: this.field,
                        readOnly: this.readOnly
                    }, function (view) {
                        view.render(function () {
                            this.fetch();
                        }.bind(this));
                    }.bind(this));
                    break;
                default:
                    this.$el.find('.subject').empty();
            }
        },

        handleSubject: function (subject, noFetch) {
            if (!noFetch) {
                this.fetch();
            }
        },

        getSubjectValue: function () {
            return this.conditionData.value;
        }

    });
});
