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

Core.define('advanced:views/account/record/panels/quotes', 'views/record/panels/relationship', function (Dep) {

    return Dep.extend({

        actionCreateRelatedQuote: function () {
            this.notify('Loading...');
            var viewName = this.getMetadata().get('clientDefs.Quote.modalViews.edit') || 'views/modals/edit';

            var attributes = {};

            [
                'billingAddressStreet',
                'billingAddressCountry',
                'billingAddressPostalCode',
                'billingAddressCity',
                'billingAddressState',
                'shippingAddressStreet',
                'shippingAddressCountry',
                'shippingAddressPostalCode',
                'shippingAddressCity',
                'shippingAddressState',

            ].forEach(function (item) {
                if (this.model.get(item)) {
                    attributes[item] = this.model.get(item);
                }
            }, this);

            this.createView('quickCreate', viewName, {
                scope: 'Quote',
                relate: {
                    model: this.model,
                    link: 'account',
                },
                attributes: attributes,
            }, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    this.collection.fetch();
                }, this);
            }, this);
        },

    });
});

