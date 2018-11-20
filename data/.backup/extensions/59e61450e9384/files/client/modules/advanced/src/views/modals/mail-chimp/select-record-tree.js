/*********************************************************************************
 * The contents of this file are subject to the CRM Advanced
 * Agreement ("License") which can be viewed at
 * http://www.espocrm.com/advanced-pack-agreement.
 * By installing or using this file, You have unconditionally agreed to the
 * terms and conditions of the License, and You may not use this file except in
 * compliance with the License.  Under the terms of the license, You shall not,
 * sublicense, resell, rent, lease, distribute, or otherwise  transfer rights
 * or usage to the software.
 * 
 * License ID: bcac485dee9efd0f36cf6842ad5b69b4
 ***********************************************************************************/

Core.define('Advanced:Views.Modals.MailChimp.SelectRecordTree','Views.Modals.SelectRecords', function (Dep) {

    return Dep.extend({

       setup: function () {
            this.filters = this.options.filters || {};
            this.boolFilterList = this.options.boolFilterList || {};
            this.primaryFilterName = this.options.primaryFilterName || null;

            if ('multiple' in this.options) {
                this.multiple = this.options.multiple;
            }

            this.createButton = false;
            this.massRelateEnabled = this.options.massRelateEnabled;

            this.buttonList = [
                {
                    name: 'cancel',
                    label: 'Cancel',
                    onClick: function (dialog) {
                        dialog.close();
                    }
                }
            ];

            if (this.multiple) {
                this.buttonList.unshift({
                    name: 'select',
                    style: 'primary',
                    label: 'Select',
                    onClick: function (dialog) {
                        var listView = this.getView('list');

                        if (listView.allResultIsChecked) {
                            var where = this.collection.where;
                            this.trigger('select', {
                                massRelate: true,
                                where: where
                            });
                        } else {
                            var list = listView.getSelected();
                            if (list.length) {
                                this.trigger('select', list);
                            }
                        }
                        dialog.close();
                    }.bind(this),
                });
            }

            this.scope = this.options.scope;
            this.listId = this.options.listId;

            this.header = this.getLanguage().translate(this.scope, 'scopeNamesPlural');

            this.waitForView('list');

            Core.require('SearchManager', function (SearchManager) {
                this.getCollectionFactory().create(this.scope, function (collection) {

                    collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;
                    collection.listId = this.listId;

                    this.collection = collection;

                    var searchManager = new SearchManager(collection, 'listSelect', null, this.getDateTime());
                    searchManager.emptyOnReset = true;
                    if (this.filters) {
                        searchManager.setAdvanced(this.filters);
                    }
                    if (this.boolFilterList) {
                        searchManager.setBool(this.boolFilterList);
                    }
                    if (this.primaryFilterName) {
                        searchManager.setPrimary(this.primaryFilterName);
                    }

                    collection.where = searchManager.getWhere();
                    collection.url = collection.name + '/action/listTree';

                    var viewName = this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.listSelectCategoryTree') ||
                                   'Record.ListTree';

                    this.listenToOnce(collection, 'sync', function () {
                        this.createView('list', viewName, {
                            collection: collection,
                            el: this.containerSelector + ' .list-container',
                            createDisabled: true,
                            selectable: true,
                            checkboxes: this.multiple,
                            massActionsDisabled: true,
                            searchManager: searchManager,
                            checkAllResultDisabled: false,
                            buttonsDisabled: true
                        }, function (list) {
                            list.once('select', function (model) {
                                this.trigger('select', model);
                                this.close();
                            }.bind(this));
                        }.bind(this));
                    }.bind(this));

                    collection.fetch();

                }.bind(this));
            }.bind(this));
        }
    });
});
