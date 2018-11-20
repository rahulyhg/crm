/************************************************************************
 * This file is part of CRM.
 *
 * CRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * CRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * CRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/

Core.define('Advanced:Views.Opportunity.Record.ItemList', ['Views.Base', 'Collection'], function (Dep, Collection) {

    return Dep.extend({

        template: 'advanced:opportunity.record.item-list',

        data: function () {
            return {
                itemDataList: this.itemDataList,
                mode: this.mode
            };
        },

        setup: function () {
            this.mode = this.options.mode;
            this.itemDataList = [];

            var itemList = this.model.get('itemList') || [];

            this.collection = new Collection();

            itemList.forEach(function (item, i) {
                var id = item.id || 'cid' + i;
                this.itemDataList.push({
                    num: i,
                    key: 'item-' + i,
                    id: id
                });
                this.getModelFactory().create('OpportunityItem', function (model) {
                    model.set(item);
                    this.collection.push(model);
                    this.createView('item-' + i, 'Advanced:Opportunity.Record.Item', {
                        el: this.options.el + ' .item-container-' + id,
                        model: model,
                        mode: this.mode
                    }, function (view) {
                        this.listenTo(view, 'change', function () {
                            this.trigger('change');
                        }, this);
                    }.bind(this));
                }, this);

            }, this);
        },

        fetch: function () {
            var itemList = [];
            this.itemDataList.forEach(function (item) {
                var data = this.getView(item.key).fetch();
                itemList.push(data);
            }, this);
            return {
                itemList: itemList
            };
        },

    });
});

