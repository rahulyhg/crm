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

Core.define('Advanced:Views.Workflow.Conditions.Float', 'Advanced:Views.Workflow.Conditions.Int', function (Dep) {

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

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.getPreferences().has('decimalMark')) {
                this.decimalMark = this.getPreferences().get('decimalMark');
            } else {
                if (this.getConfig().has('decimalMark')) {
                    this.decimalMark = this.getConfig().get('decimalMark');
                }
            }
            if (this.getPreferences().has('thousandSeparator')) {
                this.thousandSeparator = this.getPreferences().get('thousandSeparator');
            } else {
                if (this.getConfig().has('thousandSeparator')) {
                    this.thousandSeparator = this.getConfig().get('thousandSeparator');
                }
            }
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
                        value = (value !== '') ? value : null;
                        if (value !== null) {
                            value = value.split(this.thousandSeparator).join('');
                            value = value.split(this.decimalMark).join('.');
                            value = parseFloat(value);
                        }
                        this.conditionData.value = value;
                        break;
                }
            }
        },

        getSubjectValue: function () {
            var value = this.conditionData.value;
            if (typeof value === 'undefined') {
                return '';
            }
            return this.formatNumber(value);
        },

        formatNumber: function (value) {
            if (value !== null) {
                var parts = value.toString().split(".");
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);
                return parts.join(this.decimalMark);
            }
            return '';
        },
    });
});
