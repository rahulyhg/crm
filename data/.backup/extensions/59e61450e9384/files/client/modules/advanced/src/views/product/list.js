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

Core.define('Advanced:Views.Product.List', 'Views.List', function (Dep) {

    return Dep.extend({

        template: 'advanced:product.list',

        quickCreate: false,

        currentCategoryId: null,

        currentCategoryName: '',

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (!this.hasView('categories')) {
                this.loadCategories();
            }
        },

        loadCategories: function () {
            this.getCollectionFactory().create('ProductCategory', function (collection) {
                collection.url = collection.name + '/action/listTree';

                this.listenToOnce(collection, 'sync', function () {
                    this.createView('categories', 'Record.ListTree', {
                        collection: collection,
                        el: this.options.el + ' .categories-container',
                        selectable: true,
                        createDisabled: true,
                        showRoot: true,
                        rootName: this.translate('Product', 'scopeNamesPlural'),
                        buttonsDisabled: true,
                        checkboxes: false,
                        showEditLink: this.getAcl().check('ProductCategory', 'edit')
                    }, function (view) {
                        view.render();

                        this.listenTo(view, 'select', function (model) {
                            this.currentCategoryId = null;
                            this.currentCategoryName = '';

                            if (model && model.id) {
                                this.currentCategoryId = model.id;
                                this.currentCategoryName = model.get('name');
                            }
                            this.collection.whereAdditional = null;

                            if (this.currentCategoryId) {
                                this.collection.whereAdditional = [
                                    {
                                        field: 'category',
                                        type: 'inCategory',
                                        value: model.id
                                    }
                                ];
                            }

                            this.notify('Please wait...');
                            this.listenToOnce(this.collection, 'sync', function () {
                                this.notify(false);
                            }, this);
                            this.collection.fetch();

                        }, this);
                    }.bind(this));
                }, this);
                collection.fetch();
            }, this);
        },

        getCreateAttributes: function () {
            return {
                categoryId: this.currentCategoryId,
                categoryName: this.currentCategoryName
            };
        },

    });

});
