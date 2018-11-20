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

Core.define('Advanced:Views.Quote.Fields.Account', 'Views.Fields.Link', function (Dep) {

    return Dep.extend({

        select: function (model) {
            Dep.prototype.select.call(this, model);

            this.model.set('billingAddressStreet', model.get('billingAddressStreet'));
            this.model.set('billingAddressCity', model.get('billingAddressCity'));
            this.model.set('billingAddressState', model.get('billingAddressState'));
            this.model.set('billingAddressCountry', model.get('billingAddressCountry'));
            this.model.set('billingAddressPostalCode', model.get('billingAddressPostalCode'));

            this.model.set('shippingAddressStreet', model.get('shippingAddressStreet'));
            this.model.set('shippingAddressCity', model.get('shippingAddressCity'));
            this.model.set('shippingAddressState', model.get('shippingAddressState'));
            this.model.set('shippingAddressCountry', model.get('shippingAddressCountry'));
            this.model.set('shippingAddressPostalCode', model.get('shippingAddressPostalCode'));
        }

    });
});

