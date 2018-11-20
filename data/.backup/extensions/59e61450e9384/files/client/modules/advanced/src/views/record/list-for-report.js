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

Core.define('advanced:views/record/list-for-report', 'views/record/list', function (Dep) {

    return Dep.extend({

        checkAllResultMassActionList: ['export'],

        export: function () {
            var version = this.getConfig().get('version');

            var arr = version.split('.');
            if (arr.length > 2) {
                var versionNumber = parseFloat(arr[0] + '.' + arr[1]);
                if (versionNumber < 4.3) {
                    this.exportDeprecated();
                    return;
                }
            }

            var data = {};
            var fieldList = null;

            if (this.options.listLayout) {
                fieldList = [];
                this.options.listLayout.forEach(function (item) {
                    fieldList.push(item.name);
                });
            }
            if (!this.allResultIsChecked) {
                data.ids = this.checkedList;
            }

            data.id = this.options.reportId;

            if ('runtimeWhere' in this.options) {
                data.where = this.options.runtimeWhere
            }
            if ('groupValue' in this.options) {
                data.groupValue = this.options.groupValue
            }

            data.sortBy = this.collection.sortBy;
            data.asc = this.collection.asc;

            var url = 'Report/action/exportList';

            Dep.prototype.export.call(this, data, url, fieldList);

        },

        exportDeprecated: function () {
            var data = {};
            if (this.allResultIsChecked) {
                data.id = this.options.reportId;

                if ('runtimeWhere' in this.options) {
                    data.where = this.options.runtimeWhere
                }
                if ('groupValue' in this.options) {
                    data.groupValue = this.options.groupValue
                }

                $.ajax({
                    url: 'Report/action/exportList',
                    type: 'GET',
                    data: data,
                    success: function (data) {
                        if ('id' in data) {
                            window.location = '?entryPoint=download&id=' + data.id;
                        }
                    }
                });
            } else {
                data.ids = this.checkedList;

                $.ajax({
                    url: this.scope + '/action/export',
                    type: 'GET',
                    data: data,
                    success: function (data) {
                        if ('id' in data) {
                            window.location = '?entryPoint=download&id=' + data.id;
                        }
                    }
                });
            }
        }

    });

});