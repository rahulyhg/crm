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

Core.define('Advanced:Views.Quote.Record.Detail', 'Views.Record.Detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            this.dropdownItemList.push({
                name: 'printPdf',
                label: 'Print to PDF'
            });

            this.dropdownItemList.push({
                name: 'composeEmail',
                label: 'Email PDF'
            });

            if (this.getAcl().checkModel(this.model, 'edit')) {
                this.dropdownItemList.push({
                    'label': 'Duplicate',
                    'name': 'duplicate'
                });
            }

        },

        actionPrintPdf: function () {
            this.createView('pdfTemplate', 'Modals.SelectTemplate', {
                entityType: this.model.name
            }, function (view) {
                view.render();

                this.listenToOnce(view, 'select', function (model) {
                    window.open('?entryPoint=pdf&entityType='+this.model.name+'&entityId='+this.model.id+'&templateId=' + model.id, '_blank');
                }, this);
            }.bind(this));
        },

        actionComposeEmail: function () {
            this.createView('pdfTemplate', 'views/modals/select-template', {
                entityType: this.model.name
            }, function (view) {
                view.render();
                this.listenToOnce(view, 'select', function (model) {
                    this.notify('Loading...');
                    this.ajaxPostRequest('Quote/action/getAttributesForEmail', {
                        quoteId: this.model.id,
                        templateId: model.id
                    }).done(function (attributes) {
                        var viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') || 'views/modals/compose-email';
                        this.createView('composeEmail', viewName, {
                            attributes: attributes,
                            keepAttachmentsOnSelectTemplate: true
                        }, function (view) {
                            view.render();
                            this.notify(false);
                        }, this);
                    }.bind(this));
                }, this);
            }, this);
        }

    });
});

