

Core.define('views/global-search/panel', 'view', function (Dep) {

    return Dep.extend({

        template: 'global-search/panel',

        afterRender: function () {

            this.listenToOnce(this.collection, 'sync', function () {
                this.createView('list', 'views/record/list-expanded', {
                    el: this.options.el + ' .list-container',
                    collection: this.collection,
                    listLayout: {
                        rows: [
                            [
                                {
                                    name: 'name',
                                    view: 'views/global-search/name-field',
                                    params: {
                                        containerEl: this.options.el
                                    },
                                }
                            ]
                        ],
                        right: {
                            name: 'read',
                            view: 'views/global-search/scope-badge',
                            width: '80px'
                        }
                    }
                }, function (view) {
                    view.render();
                });
            }.bind(this));
            this.collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 10;
            this.collection.fetch();
        }

    });

});

