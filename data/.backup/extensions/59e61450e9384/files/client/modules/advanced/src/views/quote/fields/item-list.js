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

Core.define('Advanced:Views.Quote.Fields.ItemList', ['Views.Fields.Base', 'Model'], function (Dep, Model) {

    return Dep.extend({

        detailTemplate: 'advanced:quote.fields.item-list.detail',

        listTemplate: 'advanced:quote.fields.item-list.detail',

        editTemplate: 'advanced:quote.fields.item-list.edit',

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
                showFields: (this.model.get('itemList') || []).length > 0,
                isEmpty: (this.model.get('itemList') || []).length === 0,
                mode: this.mode
            };
        },

        getAttributeList: function () {
            return ['itemList'];
        },

        setMode: function (mode) {
            Dep.prototype.setMode.call(this, mode);
            if (this.isRendered()) {
                this.getView('shippingCost').setMode(mode);
                this.getView('currency').setMode(mode);
            }
        },

        setup: function () {
            var itemList = this.model.get('itemList') || [];
            this.lastNumber = itemList.length;

            this.listenTo(this.model, 'change:amountCurrency', function (model, v, o) {
                if (!o.ui) return;
                var currency = this.model.get('amountCurrency');

                var itemList = Core.Utils.cloneDeep(this.model.get('itemList') || []);
                itemList.forEach(function (item) {
                    item.listPriceCurrency = currency;
                    item.unitPriceCurrency = currency;
                    item.amountCurrency = currency;
                }, this);

                this.model.set('preDiscountedAmountCurrency', currency);
                this.model.set('shippingCostCurrency', currency);
                this.model.set('taxAmountCurrency', currency);
                this.model.set('grandTotalAmountCurrency', currency);
                this.model.set('discountAmountCurrency', currency);

                this.model.set('itemList', itemList);

            }, this);

            this.listenTo(this.model, 'change:taxRate', function (model, v, o) {
                if (!o.ui) return;
                var taxRate = this.model.get('taxRate') || 0;
                var itemList = Core.Utils.cloneDeep(this.model.get('itemList') || []);
                itemList.forEach(function (item) {
                    item.taxRate = taxRate;
                }, this);
                this.model.set('itemList', itemList);
                this.calculateAmount();
            }, this);

            this.listenTo(this.model, 'change:shippingCost', function (model, v, o) {
                if (!o.ui) return;
                this.calculateAmount();
            }, this);

            this.createView('preDiscountedAmount', 'Fields.Currency', {
                el: this.options.el + ' .field-preDiscountedAmount',
                model: this.model,
                mode: 'detail',
                inlineEditDisabled: true,
                defs: {
                    name: 'preDiscountedAmount'
                },
                hideCurrency: true
            });

            this.createView('discountAmount', 'Fields.Currency', {
                el: this.options.el + ' .field-discountAmount',
                model: this.model,
                mode: 'detail',
                inlineEditDisabled: true,
                defs: {
                    name: 'discountAmount'
                },
                hideCurrency: true
            });

            this.createView('amount', 'Fields.Currency', {
                el: this.options.el + ' .field-amount-bottom',
                model: this.model,
                mode: 'detail',
                inlineEditDisabled: true,
                defs: {
                    name: 'amount'
                },
                hideCurrency: true
            });

            this.createView('taxAmount', 'Fields.Currency', {
                el: this.options.el + ' .field-taxAmount',
                model: this.model,
                mode: 'detail',
                inlineEditDisabled: true,
                defs: {
                    name: 'taxAmount'
                },
                hideCurrency: true
            });

            this.createView('shippingCost', 'Advanced:Quote.Fields.ShippingCost', {
                el: this.options.el + ' .field-shippingCost',
                model: this.model,
                mode: this.mode,
                inlineEditDisabled: true,
                defs: {
                    name: 'shippingCost'
                },
                hideCurrency: true
            });

            this.createView('grandTotalAmount', 'Fields.Currency', {
                el: this.options.el + ' .field-grandTotalAmount',
                model: this.model,
                mode: 'detail',
                inlineEditDisabled: true,
                defs: {
                    name: 'grandTotalAmount'
                },
                hideCurrency: true
            });


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
            var recordView = this.getParentView().getParentView().getParentView();

            var itemList = this.model.get('itemList') || [];

            if (itemList.length) {
                this.showAdditionalFields();
                if (recordView.setFieldReadOnly) {
                    recordView.setFieldReadOnly('amount');
                }
            } else {
                if (recordView.setFieldNotReadOnly) {
                    recordView.setFieldNotReadOnly('amount');
                }
                this.hideAdditionalFields();
            }
        },

        showAdditionalFields: function () {
            this.$el.find('.currency-row').removeClass('hidden');
            this.$el.find('.totals-row').removeClass('hidden');
        },

        hideAdditionalFields: function () {
            this.$el.find('.currency-row').addClass('hidden');
            this.$el.find('.totals-row').addClass('hidden');
        },

        afterRender: function () {
            this.$container = this.$el.find('.container');

            this.handleCurrencyField();

            this.createView('itemList', 'Advanced:Quote.Record.ItemList', {
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

            if (this.model.isNew()) {
                var itemList = this.model.get('itemList') || [];
                itemList.forEach(function (item) {
                    if (!item.id) {
                        var id = 'cid' + this.lastNumber;
                        this.lastNumber++;
                        item.id = id;
                    }
                }, this);
                this.calculateAmount();
            }
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
                listPriceCurrency: this.model.get('amountCurrency'),
                unitPriceCurrency: this.model.get('amountCurrency'),
                isTaxable: true,
                taxRate: this.model.get('taxRate') || 0
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
            var itemList = this.model.get('itemList') || [];

            var currency = this.model.get('amountCurrency');

            var amount = 0;
            itemList.forEach(function(item) {
                amount += item.amount || 0;
            }, this);

            amount = Math.round(amount * 100) / 100;
            this.model.set('amount', amount);

            var preDiscountedAmount = 0;
            itemList.forEach(function(item) {
                preDiscountedAmount += (item.listPrice || 0) * (item.quantity || 0);
            }, this);
            preDiscountedAmount = Math.round(preDiscountedAmount * 100) / 100;
            this.model.set({
                'preDiscountedAmount': preDiscountedAmount,
                'preDiscountedAmountCurrency': currency
            });

            var taxAmount = 0;
            itemList.forEach(function(item) {
                taxAmount += (item.amount || 0) * ((item.taxRate || 0) / 100.0);
            }, this);
            taxAmount = Math.round(taxAmount * 100) / 100;
            this.model.set({
                'taxAmount': taxAmount,
                'taxAmountCurrency': currency
            });

            var shippingCost = this.model.get('shippingCost') || 0;

            var discountAmount = preDiscountedAmount - amount;
            discountAmount = Math.round(discountAmount * 100) / 100;
            this.model.set({
                'discountAmount': discountAmount,
                'discountAmountCurrency': currency
            });

            var grandTotalAmount = amount + taxAmount + shippingCost;
            grandTotalAmount = Math.round(grandTotalAmount * 100) / 100;
            this.model.set({
                'grandTotalAmount': grandTotalAmount,
                'grandTotalAmountCurrency': currency
            });


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

