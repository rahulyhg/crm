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

Core.define('advanced:views/target-list/record/panels/sync-with-reports', 'views/record/panels/side', function (Dep) {

    return Dep.extend({

        fieldList: [
            'syncWithReportsEnabled',
            'syncWithReports',
            'syncWithReportsUnlink'
        ],

        actionList: [
          {
            "name": "syncWithReport",
            "label": "Sync Now",
            "acl": "edit",
            "action": "syncWithReports"
          }
        ],

        setup: function () {
            Dep.prototype.setup.call(this);
        },

        actionSyncWithReports: function () {
            this.notify('Please wait...');
            $.ajax({
                url: 'Report/action/syncTargetListWithReports',
                type: 'Post',
                data: JSON.stringify({
                    targetListId: this.model.id
                })
            }).done(function () {
                this.notify('Done', 'success');
                this.model.trigger('after:relate');
            }.bind(this));

        },
    });
});

