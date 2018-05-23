

Core.define('crm:views/document/list', 'views/list', function (Dep) {

    return Dep.extend({

        template: 'crm:document/list',

        quickCreate: true,

        currentCategoryId: null,

        currentCategoryName: '',

        categoryScope: 'DocumentFolder',

        categoryField: 'folder',

        categoryFilterType: 'inCategory',

        data: function () {
            var data = {};
            data.categoriesDisabled = this.categoriesDisabled;
            return data;
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            this.categoriesDisabled = this.categoriesDisabled ||
                                   this.getMetadata().get('scopes.' + this.categoryScope + '.disabled') ||
                                   !this.getAcl().checkScope(this.categoryScope);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (!this.categoriesDisabled && !this.hasView('categories')) {
                this.loadCategories();
            }
        },

        getTreeCollection: function (callback) {
            this.getCollectionFactory().create(this.categoryScope, function (collection) {
                collection.url = collection.name + '/action/listTree';

                this.collection.treeCollection = collection;

                this.listenToOnce(collection, 'sync', function () {
                    callback.call(this, collection);
                }, this);
                collection.fetch();
            }, this);
        },

        loadCategories: function () {
            this.getTreeCollection(function (collection) {
                this.createView('categories', 'views/record/list-tree', {
                    collection: collection,
                    el: this.options.el + ' .categories-container',
                    selectable: true,
                    createDisabled: true,
                    showRoot: true,
                    rootName: this.translate(this.scope, 'scopeNamesPlural'),
                    buttonsDisabled: true,
                    checkboxes: false,
                    showEditLink: this.getAcl().check(this.categoryScope, 'edit')
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
                                    field: this.categoryField,
                                    type: this.categoryFilterType,
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
                }, this);

            }, this);
        },

        getCreateAttributes: function () {
            return {
                folderId: this.currentCategoryId,
                folderName: this.currentCategoryName
            };
        }

    });

});
