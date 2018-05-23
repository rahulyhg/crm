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

 Core.define('Advanced:Views.Report.Fields.EmailSendingWeekdays', 'Views.Fields.Base', function (Dep) {

    return Dep.extend({

        editTemplate: 'advanced:report.fields.email-sending-weekdays.edit',

        detailTemplate: 'advanced:report.fields.email-sending-weekdays.detail',

        data: function () {
            var weekday = this.model.get(this.name) || '';
            var weekdays = {};
            for (i = 0; i < 7; i++) {
                weekdays[i] = (weekday.indexOf(i.toString())) > -1 || false;
            }
            return _.extend({
                selectedWeekdays: weekdays,
                days: this.translate('dayNamesShort', 'lists')
            }, Dep.prototype.data.call(this));
        },

        fetch: function () {
            var data = {};
            var value = '';
            this.$element.each(function(i){
                if ($(this).is(':checked')) {
                    value += $(this).val();
                }
            });
            data[this.name] = value;
            return data;
        },

    });
});
