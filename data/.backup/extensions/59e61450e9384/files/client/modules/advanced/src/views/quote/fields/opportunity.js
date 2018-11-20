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

Core.define('Advanced:Views.Quote.Fields.Opportunity', 'Views.Fields.Link', function (Dep) {

    return Dep.extend({

        getSelectFilters: function () {
            if (this.model.get('accountId')) {
                return {
                    'account': {
                        type: 'equals',
                        field: 'accountId',
                        value: this.model.get('accountId'),
                        valueName: this.model.get('accountName'),
                    }
                };
            }
        },

        select: function (model) {
            Dep.prototype.select.call(this, model);

            if (this.model.isNew()) {

                this.ajaxGetRequest('Quote/action/getAttributesFromOpportunity', {
                    opportunityId: model.id
                }).success(function (attributes) {

                    var a = {};
                    for (var item in attributes) {
                        if ( !~['amountCurrency', 'name'].indexOf(item)) {
                            a[item] = attributes[item];
                        }
                    }
                    this.model.set(a);
                    this.model.set('amountCurrency', attributes.amountCurrency, {ui: true});

                }.bind(this));
            }
        }

    });
});

