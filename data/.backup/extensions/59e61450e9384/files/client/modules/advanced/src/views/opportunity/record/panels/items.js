/************************************************************************
 * This file is part of Samex CRM.
 *
 * Samex CRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * Samex CRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Samex CRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Samex CRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/

Core.define('Advanced:Views.Opportunity.Record.Panels.Items', 'Views.Record.Panels.Bottom', function (Dep) {

    return Dep.extend({

        template: 'advanced:opportunity.record.panels.items',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.createView('itemList', 'Advanced:Opportunity.Fields.ItemList', {
                model: this.model,
                el: this.options.el + ' .field-itemList',
                defs: {
                    name: 'itemList'
                },
                mode: this.mode
            });
        },

        getFields: function () {
            var fields = {};
            fields.itemList = this.getView('itemList');
            return fields;
        },

    });
});

