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

Core.define('Advanced:Views.Workflow.Conditions.Int', 'Advanced:Views.Workflow.Conditions.Base', function (Dep) {

    return Dep.extend({

        template: 'advanced:workflow.conditions.base',

        comparisonList: [
            'equals',
            'wasEqual',
            'notEquals',
            'wasNotEqual',
            'greaterThan',
            'lessThan',
            'greaterThanOrEquals',
            'lessThanOrEquals',
            'changed'
        ],

        defaultConditionData: {
            comparison: 'equals',
            subjectType: 'value'
        },

        fetchSubject: function () {
            var $subject = this.$el.find('[name="subject"]');

            delete this.conditionData.value;
            delete this.conditionData.field;

            if ($subject.size()) {
                switch (this.conditionData.subjectType) {
                    case 'field':
                        this.conditionData.field = $subject.val();
                        break;
                    case 'value':
                        var value = $subject.val();
                        if (value === '') {
                            value = null;
                        } else {
                            value = parseInt(value)
                        }
                        this.conditionData.value = value;
                        break;
                }
            }
        },

        getSubjectValue: function () {
            return this.conditionData.value;
        }
    });
});
