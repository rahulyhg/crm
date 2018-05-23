
Core.define('controllers/base', 'controller', function (Dep) {

    return Dep.extend({

        login: function () {
            this.entire('views/login', {}, function (login) {
                login.render();
                login.on('login', function (data) {
                    this.trigger('login', data);
                }.bind(this));
            }.bind(this));
        },

        logout: function () {
            this.trigger('logout');
        },

        clearCache: function (options) {
            this.entire('views/clear-cache', {
                cache: this.getCache()
            }, function (view) {
                view.render();
            });
        },

        error404: function () {
            this.entire('views/base', {template: 'errors/404'}, function (view) {
                view.render();
            });
        },

        error403: function () {
            this.entire('views/base', {template: 'errors/403'}, function (view) {
                view.render();
            });
        },

    });
});

