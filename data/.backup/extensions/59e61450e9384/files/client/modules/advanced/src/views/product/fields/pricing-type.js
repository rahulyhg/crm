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

Core.define('Advanced:Views.Product.Fields.PricingType', 'Views.Fields.Enum', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:pricingType', function () {
                this.setupCalculation();
            }, this);
            this.on('change', function () {
                this.calculate();
            }, this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.setupCalculation();
        },

        setupCalculation: function () {
            this.stopListening(this.model, 'change:listPrice');
            this.stopListening(this.model, 'change:costPrice');
            this.stopListening(this.model, 'change:listPriceCurrency');
            this.stopListening(this.model, 'change:costPriceCurrency');

            var pricingType = this.model.get('pricingType');

            switch (pricingType) {
                case 'Same as List':
                    this.listenTo(this.model, 'change:listPrice', this.calculate, this);
                    this.listenTo(this.model, 'change:listPriceCurrency', this.calculate, this);
                    break;
                case 'Discount from List':
                    this.listenTo(this.model, 'change:listPrice', this.calculate, this);
                    this.listenTo(this.model, 'change:listPriceCurrency', this.calculate, this);
                    this.listenTo(this.model, 'change:pricingFactor', this.calculate, this);
                    break;
                case 'Markup over Cost':
                    this.listenTo(this.model, 'change:costPrice', this.calculate, this);
                    this.listenTo(this.model, 'change:costPriceCurrency', this.calculate, this);
                    this.listenTo(this.model, 'change:listPriceCurrency', this.calculate, this);
                    this.listenTo(this.model, 'change:pricingFactor', this.calculate, this);
                    break;
                case 'Profit Margin':
                    this.listenTo(this.model, 'change:costPrice', this.calculate, this);
                    this.listenTo(this.model, 'change:costPriceCurrency', this.calculate, this);
                    this.listenTo(this.model, 'change:listPriceCurrency', this.calculate, this);
                    this.listenTo(this.model, 'change:pricingFactor', this.calculate, this);
                    break;
            }
        },

        calculate: function () {
            var pricingType = this.model.get('pricingType');
            var pricingFactor = this.model.get('pricingFactor') || 0.0;

            switch (pricingType) {
                case 'Same as List':
                    this.model.set('unitPrice', this.model.get('listPrice'));
                    this.model.set('unitPriceCurrency', this.model.get('listPriceCurrency'));
                    break;
                case 'Discount from List':
                    var currency = this.model.get('listPriceCurrency');
                    var value = this.model.get('listPrice');
                    value = value - value * pricingFactor / 100.0;
                    this.model.set({
                        'unitPrice': value,
                        'unitPriceCurrency': currency
                    });
                    break;
                case 'Markup over Cost':
                    var listCurrency = this.model.get('listPriceCurrency');
                    var costCurrency = this.model.get('costPriceCurrency');

                    var value = this.model.get('costPrice');
                    value = pricingFactor / 100.0 * value + value;

                    var baseCurrency = this.getConfig().get('baseCurrency');
                    var rates = this.getConfig().get('currencyRates') || {};

                    value = value * (rates[costCurrency] || 1.0);
                    value = value / (rates[listCurrency] || 1.0);

                    value = Math.round(value * 100) / 100;

                    this.model.set({
                        'unitPrice': value,
                        'unitPriceCurrency': listCurrency
                    });
                    break;
                case 'Profit Margin':
                    var listCurrency = this.model.get('listPriceCurrency');
                    var costCurrency = this.model.get('costPriceCurrency');

                    var value = this.model.get('costPrice');
                    value = value / (1 - pricingFactor / 100.0);

                    var baseCurrency = this.getConfig().get('baseCurrency');
                    var rates = this.getConfig().get('currencyRates') || {};

                    value = value * (rates[costCurrency] || 1.0);
                    value = value / (rates[listCurrency] || 1.0);

                    value = Math.round(value * 100) / 100;

                    this.model.set({
                        'unitPrice': value,
                        'unitPriceCurrency': listCurrency
                    });
                    break;
            }
        }

    });

});
