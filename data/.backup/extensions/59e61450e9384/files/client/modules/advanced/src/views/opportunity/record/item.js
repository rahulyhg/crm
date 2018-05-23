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

Core.define('Advanced:Views.Opportunity.Record.Item', 'Views.Base', function (Dep) {

    return Dep.extend({

        template: 'advanced:opportunity.record.item',

        data: function () {
            return {
                id: this.model.id,
                mode: this.mode
            };
        },

        setup: function () {
            this.mode = this.options.mode;

            this.createView('quantity', 'Fields.Float', {
                model: this.model,
                defs: {
                    name: 'quantity'
                },
                mode: this.mode,
                el: this.options.el + ' .field-quantity',
                inlineEditDisabled: true
            }, function (view) {
                this.listenTo(view, 'change', function () {
                    setTimeout(function () {
                        this.trigger('change');
                    }.bind(this), 50);
                }, this);
            }.bind(this));

            this.createView('name', 'Advanced:OpportunityItem.Fields.Name', {
                model: this.model,
                defs: {
                    name: 'name',
                },
                mode: this.mode,
                el: this.options.el + ' .field-name',
                inlineEditDisabled: true
            }, function (view) {
                this.listenTo(view, 'change', function () {
                    setTimeout(function () {
                        this.trigger('change');
                    }.bind(this), 50);
                }, this);
            }.bind(this));

            this.createView('unitPrice', 'Advanced:OpportunityItem.Fields.UnitPrice', {
                model: this.model,
                defs: {
                    name: 'unitPrice',
                },
                mode: this.mode,
                el: this.options.el + ' .field-unitPrice',
                inlineEditDisabled: true,
                hideCurrency: true
            }, function (view) {
                this.listenTo(view, 'change', function () {
                    setTimeout(function () {
                        this.trigger('change');
                    }.bind(this), 50);
                }, this);
            }.bind(this));

            this.createView('amount', 'Fields.Currency', {
                model: this.model,
                defs: {
                    name: 'amount',
                },
                mode: 'detail',
                el: this.options.el + ' .field-itemAmount',
                inlineEditDisabled: true,
                hideCurrency: true
            });

            this.createView('description', 'views/fields/text', {
                model: this.model,
                defs: {
                    name: 'description',
                    params: {
                        rows: 2
                    }
                },
                mode: this.mode === 'edit' ? 'edit' : 'list',
                el: this.options.el + ' .field-description',
                inlineEditDisabled: true
            }, function (view) {
                this.listenTo(view, 'change', function () {
                    setTimeout(function () {
                        this.trigger('change');
                    }.bind(this), 50);
                }, this);
            }.bind(this));
        },

        afterRender: function () {
            this.listenTo(this.getView('quantity'), 'change', function () {
                this.calculateAmount();
            }, this);

            this.listenTo(this.getView('unitPrice'), 'change', function () {
                this.calculateAmount();
            }, this);

            this.listenTo(this.getView('name'), 'change', function () {
                this.calculateAmount();
            }, this);
        },

        calculateAmount: function () {
            var quantity = this.model.get('quantity');
            var unitPrice = this.model.get('unitPrice');
            var unitPriceCurrency = this.model.get('unitPriceCurrency');

            var amount = quantity * unitPrice;
            amount = Math.round(amount * 100) / 100;
            var amountCurrency = unitPriceCurrency;

            this.model.set({
                amount: amount,
                amountCurrency: amountCurrency
            });
        },

        fetch: function () {
            var data = {
                id: this.model.id,
                quantity: this.model.get('quantity'),
                unitPrice: this.model.get('unitPrice'),
                unitPriceCurrency: this.model.get('unitPriceCurrency'),
                amount: this.model.get('amount'),
                amountCurrency: this.model.get('amountCurrency'),
                productId: this.model.get('productId') || null,
                productName: this.model.get('productName') || null,
                name: this.model.get('name'),
                description: this.model.get('description')
            };
            return data;
        }

    });
});

