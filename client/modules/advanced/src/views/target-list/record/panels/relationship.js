/************************************************************************
 * This file is part of CoreCRM.
 *
 * CoreCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * CoreCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * CoreCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CoreCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/

Core.define('advanced:views/target-list/record/panels/relationship', 'crm:views/target-list/record/panels/relationship', function (Dep) {

    return Dep.extend({

        actionPopulateFromReport: function (data) {
            var link = data.link;

            var filterName = 'list' + Core.Utils.upperCaseFirst(link);

            this.notify('Loading...');
            this.createView('dialog', 'Modals.SelectRecords', {
                scope: 'Report',
                multiple: false,
                createButton: false,
                primaryFilterName: filterName,
            }, function (dialog) {
                dialog.render();
                this.notify(false);
                dialog.once('select', function (selectObj) {
                    var data = {};

                    data.id = selectObj.id;
                    data.targetListId = this.model.id;

                    $.ajax({
                        url: 'Report/action/populateTargetList',
                        type: 'POST',
                        data: JSON.stringify(data),
                        success: function () {
                            this.notify('Linked', 'success');
                            this.collection.fetch();
                        }.bind(this)
                    });
                }.bind(this));
            }.bind(this));
        }

    });
});

