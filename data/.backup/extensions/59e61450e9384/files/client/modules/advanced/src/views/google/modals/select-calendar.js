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

Core.define('Advanced:Views.Google.Modals.SelectCalendar', 'Views.Modal', function (Dep) {

    return Dep.extend({

        cssName: 'select-folder-modal',

        template: 'advanced:google.modals.select-calendar',

        data: function () {
            return {
                calendars: this.options.calendars,
            };
        },

        events: {
            'click button[data-action="select"]': function (e) {
                var value = $(e.currentTarget).data('value');
                this.trigger('select', value);
            },
        },

        setup: function () {
            this.buttonList = [
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];

        },

    });
});

