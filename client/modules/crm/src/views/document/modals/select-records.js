

Core.define('crm:views/document/modals/select-records', 'views/modals/select-records', function (Dep) {

    return Dep.extend({

        template: 'crm:document/modals/select-records',

        categoryScope: 'DocumentFolder',

        categoryField: 'folder',

        categoryFilterType: 'inCategory',

        data: function () {
            var data = Dep.prototype.data.call(this);
            data.categoriesDisabled = this.categoriesDisabled;
            return data;
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            this.categoriesDisabled = this.categoriesDisabled ||
                                   this.getMetadata().get('scopes.' + this.categoryScope + '.disabled') ||
                                   !this.getAcl().checkScope(this.categoryScope);
        },

        loadList: function () {
            this.loadCategories();
            Dep.prototype.loadList.call(this);
        },

        loadCategories: function () {
            this.getCollectionFactory().create(this.categoryScope, function (collection) {
                collection.url = collection.name + '/action/listTree';

                collection.data.onlyNotEmpty = true;

                this.listenToOnce(collection, 'sync', function () {
                    this.createView('categories', 'views/record/list-tree', {
                        collection: collection,
                        el: this.options.el + ' .categories-container',
                        selectable: true,
                        createDisabled: true,
                        showRoot: true,
                        rootName: this.translate(this.scope, 'scopeNamesPlural'),
                        buttonsDisabled: true,
                        checkboxes: false
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
                    }.bind(this));
                }, this);
                collection.fetch();
            }, this);
        }

    });

});
