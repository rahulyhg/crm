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

Core.define('advanced:views/quote/record/detail-small', 'views/record/detail-small', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.handleAmountField();

            this.listenTo(this.model, 'change:itemList', function () {
                this.handleAmountField();
            }, this);
        },

        handleAmountField: function () {
            var itemList = this.model.get('itemList') || [];
            if (!itemList.length) {
                this.setFieldNotReadOnly('amount');
            } else {
                this.setFieldReadOnly('amount');
            }
        }

    });
});

