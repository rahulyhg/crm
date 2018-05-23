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

Core.define('Advanced:Views.Opportunity.Fields.ItemList', ['Views.Fields.Base', 'Model'], function (Dep, Model) {

    return Dep.extend({

        detailTemplate: 'advanced:opportunity.fields.item-list.detail',

        listTemplate: 'advanced:opportunity.fields.item-list.detail',

        editTemplate: 'advanced:opportunity.fields.item-list.edit',

        lastNumber: 0,

        events: {
            'click [data-action="removeItem"]': function (e) {
                var id = $(e.currentTarget).attr('data-id');
                this.removeItem(id);
            },
            'click [data-action="addItem"]': function (e) {
                this.addItem();
            }
        },

        data: function () {
            return {
                showCurrency: (this.model.get('itemList') || []).length > 0,
                isEmpty: (this.model.get('itemList') || []).length === 0,
                mode: this.mode
            };
        },

        setMode: function (mode) {
            Dep.prototype.setMode.call(this, mode);
            if (this.isRendered()) {
                this.getView('currency').setMode(mode);
            }
        },

        getAttributeList: function () {
            return ['itemList'];
        },

        setup: function () {
            var itemList = this.model.get('itemList') || [];
            this.lastNumber = itemList.length;

            this.listenTo(this.model, 'change:amountCurrency', function (model, v, o) {
                if (!o.ui) return;
                var currency = this.model.get('amountCurrency');
                var itemList = Core.Utils.cloneDeep(this.model.get('itemList') || []);
                itemList.forEach(function (item) {
                    item.unitPriceCurrency = currency;
                    item.amountCurrency = currency;
                }, this);
                this.model.set('itemList', itemList);
            }, this);

            this.currencyModel = new Model();

            this.currencyModel.set('currency', this.model.get('amountCurrency') || this.getPreferences().get('defaultCurrency') || this.getConfig().get('defaultCurrency'));
            this.createView('currency', 'Fields.Enum', {
                el: this.options.el + ' .field-currency',
                model: this.currencyModel,
                mode: this.mode,
                inlineEditDisabled: true,
                defs: {
                    name: 'currency',
                    params: {
                        options: this.getConfig().get('currencyList') || []
                    }
                }
            });

            this.listenTo(this.model, 'change:amountCurrency', function () {
                this.currencyModel.set('currency', this.model.get('amountCurrency'), {preventLoop: true});
            }, this);

            this.listenTo(this.currencyModel, 'change:currency', function (model, o) {
                if (o.preventLoop) return;
                this.model.set('amountCurrency', model.get('currency'), {ui: true});
            }, this);
        },

        handleCurrencyField: function () {
            var recordView = this.getParentView().getParentView();

            var itemList = this.model.get('itemList') || [];

            if (itemList.length) {
                this.showCurrencyField();
                if (recordView.setFieldReadOnly) {
                    recordView.setFieldReadOnly('amount');
                }
            } else {
                if (recordView.setFieldNotReadOnly) {
                    recordView.setFieldNotReadOnly('amount');
                }
                this.hideCurrencyField();
            }
        },

        showCurrencyField: function () {

            this.$el.find('.field-currency').removeClass('hidden');
        },

        hideCurrencyField: function () {
            this.$el.find('.field-currency').addClass('hidden');
        },

        afterRender: function () {
            this.$container = this.$el.find('.container');

            this.handleCurrencyField();

            /*if (this.mode == 'edit') {
                var model = this.currencyModel;

                model.set('currency', this.model.get('amountCurrency') || this.getPreferences().get('defaultCurrency') || this.getConfig().get('defaultCurrency'));

                this.createView('currency', 'Fields.Enum', {
                    el: this.options.el + ' .field-currency',
                    model: model,
                    mode: 'edit',
                    defs: {
                        name: 'currency',
                        params: {
                            options: this.getConfig().get('currencyList') || []
                        }
                    }
                }, function (view) {
                    view.render();
                }.bind(this));
            }*/

            this.createView('itemList', 'Advanced:Opportunity.Record.ItemList', {
                el: this.options.el + ' .item-list-container',
                model: this.model,
                mode: this.mode
            }, function (view) {
                this.listenTo(view, 'after:render', function () {
                    if (this.mode == 'edit') {
                        this.$el.find('.item-list-internal-container').sortable({
                            handle: '.drag-icon',
                            stop: function () {
                                var idList = [];
                                this.$el.find('.item-list-internal-container').children().each(function (i, el) {
                                    idList.push($(el).attr('data-id'));
                                });
                                this.reOrder(idList);
                            }.bind(this),
                        });
                    }
                }, this);
                view.render();

                this.listenTo(view, 'change', function () {
                    this.trigger('change');
                    this.calculateAmount();
                }, this);
            }.bind(this));
        },

        fetchItemList: function () {
            return (this.getView('itemList').fetch() || {}).itemList || [];
        },

        fetch: function () {
            var data = {};
            if (this.hasView('currency')) {
                data.amountCurrency = this.getView('currency').fetch().currency;
            }
            data.itemList = this.fetchItemList();
            return data;
        },

        addItem: function () {
            var id = 'cid' + this.lastNumber;
            this.lastNumber++;
            var data = {
                id: id,
                quantity: 1,
                unitPriceCurrency: this.model.get('amountCurrency')
            };
            var itemList = Core.Utils.clone(this.fetchItemList());
            itemList.push(data);
            this.model.set('itemList', itemList);
            this.calculateAmount();
        },

        removeItem: function (id) {
            var itemList = Core.Utils.clone(this.fetchItemList());
            var index = -1;
            itemList.forEach(function (item, i) {
                if (item.id === id) {
                    index = i;
                }
            }, this);

            if (~index) {
                itemList.splice(index, 1);
            }
            this.model.set('itemList', itemList);
            this.calculateAmount();
        },

        calculateAmount: function () {
            var amount = 0;
            var itemList = this.model.get('itemList') || [];
            itemList.forEach(function(item) {
                amount += item.amount || 0;
            }, this);

            amount = Math.round(amount * 100) / 100;

            this.model.set('amount', amount);
        },

        reOrder: function (idList) {
            var orderedItemList = [];
            var itemList = this.model.get('itemList') || [];

            idList.forEach(function (id) {
                itemList.forEach(function (item) {
                    if (item.id === id) {
                        orderedItemList.push(item);
                    }
                }, this);
            }, this);

            this.model.set('itemList', orderedItemList);
        }

    });
});

