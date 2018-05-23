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

Core.define('advanced:views/quote/record/item-list', ['views/base', 'collection'], function (Dep, Collection) {

    return Dep.extend({

        template: 'advanced:quote/record/item-list',

        data: function () {
            return {
                itemDataList: this.itemDataList,
                mode: this.mode,
                hideTaxRate: this.noTax && this.mode === 'detail',
                showRowActions: this.showRowActions
            };
        },

        events: {
            'click .action': function (e) {
                var $el = $(e.currentTarget);
                var action = $el.data('action');
                var method = 'action' + Core.Utils.upperCaseFirst(action);
                if (typeof this[method] == 'function') {
                    var data = $el.data();
                    this[method](data, e);
                    e.preventDefault();
                }
            }
        },

        setup: function () {
            this.mode = this.options.mode;
            this.itemDataList = [];

            var itemList = this.model.get('itemList') || [];

            this.collection = new Collection();

            this.collection.name = 'QuoteItem';

            this.collection.total = itemList.length;

            this.wait(true);

            this.noTax = true;

            itemList.forEach(function (item, i) {
                if (item.taxRate) {
                    this.noTax = false;
                }
            }, this);

            this.showRowActions = this.mode == 'detail' && this.getAcl().checkModel(this.model, 'edit');

            this.getModelFactory().create('QuoteItem', function (modelSeed) {
                itemList.forEach(function (item, i) {
                    var model = modelSeed.clone();

                    model.name = 'QuoteItem';

                    var id = item.id || 'cid' + i;
                    this.itemDataList.push({
                        num: i,
                        key: 'item-' + i,
                        id: id
                    });

                    model.set(item);
                    this.collection.push(model);
                    this.createView('item-' + i, 'advanced:views/quote/record/item', {
                        el: this.options.el + ' .item-container-' + id,
                        model: model,
                        mode: this.mode,
                        noTax: this.noTax,
                        showRowActions: this.showRowActions
                    }, function (view) {
                        this.listenTo(view, 'change', function () {
                            this.trigger('change');
                        }, this);
                    }, this);

                    if (i == itemList.length - 1) {
                        this.wait(false);
                    }
                }, this);

                if (itemList.length === 0) {
                    this.wait(false);
                }
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

        actionQuickView: function (data) {
            data = data || {};
            var id = data.id;
            if (!id) return;

            var model = null;
            if (this.collection) {
                model = this.collection.get(id);
            }

            var scope = this.collection.name;

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.detail') || 'views/modals/detail';

            this.notify('Loading...');
            this.createView('modal', viewName, {
                scope: scope,
                model: model,
                id: id
            }, function (view) {
                this.listenToOnce(view, 'after:render', function () {
                    Core.Ui.notify(false);
                });
                view.render();

                this.listenToOnce(view, 'remove', function () {
                    this.clearView('modal');
                }, this);

                this.listenToOnce(view, 'after:edit-cancel', function () {
                    this.actionQuickView({id: view.model.id, scope: view.model.name});
                }, this);

                this.listenToOnce(view, 'after:save', function (model) {
                    this.trigger('after:save', model);
                }, this);
            }, this);
        },

        actionQuickEdit: function (data) {
            data = data || {}
            var id = data.id;
            if (!id) return;

            var model = null;
            if (this.collection) {
                model = this.collection.get(id);
            }
            if (!data.scope && !model) {
                return;
            }

            var scope = this.collection.name;

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.edit') || 'views/modals/edit';

            this.notify('Loading...');
            this.createView('modal', viewName, {
                scope: scope,
                id: id,
                model: model,
                fullFormDisabled: data.noFullForm,
                returnUrl: '#' + this.model.name + '/view/' + this.model.id,
                returnDispatchParams: {
                    controller: this.model.name,
                    action: 'view',
                    options: {
                        id: this.model.id,
                        isReturn: true
                    }
                }
            }, function (view) {
                view.once('after:render', function () {
                    Core.Ui.notify(false);
                });

                view.render();

                this.listenToOnce(view, 'remove', function () {
                    this.clearView('modal');
                }, this);

                this.listenToOnce(view, 'after:save', function (m) {
                    var model = this.collection.get(m.id);
                    if (model) {
                        model.set(m.getClonedAttributes());
                    }

                    this.trigger('after:save', m);
                }, this);
            }, this);
        }
    });
});
