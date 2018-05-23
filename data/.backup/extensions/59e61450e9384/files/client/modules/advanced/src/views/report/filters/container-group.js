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

Core.define('advanced:views/report/filters/container-group', 'view', function (Dep) {

    return Dep.extend({

        template: 'advanced:report/filters/container-group',

        events: {
            'click > a[data-action="removeGroup"]': function () {
                this.trigger('remove-item');
            }
        },

        data: function () {
            return {
                type: this.type,
                noOffset: this.options.level > 3
            };
        },

        setup: function () {
            this.filterData = this.options.filterData;
            this.scope = this.options.scope;
            this.type = this.filterData.type;

            this.createView('node', 'advanced:views/report/filters/node', {
                el: this.getSelector() + ' > .node',
                scope: this.scope,
                dataList: this.filterData.params.value || [],
                level: this.options.level,
                filterData: this.filterData
            });
        },

        fetch: function () {
            var data = {
                id: this.filterData.id,
                type: this.filterData.type,
                params: {
                    type: this.filterData.type,
                    value: this.getView('node').fetch()
                }
            };

            return data;
        }

    });
});
