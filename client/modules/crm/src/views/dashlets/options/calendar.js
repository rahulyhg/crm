

Core.define('crm:views/dashlets/options/calendar', 'views/dashlets/options/base', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.manageUsersField();
            this.listenTo(this.model, 'change:mode', this.manageUsersField, this);
        },


        init: function () {
            Dep.prototype.init.call(this);
            this.fields.enabledScopeList.options = this.getConfig().get('calendarEntityList') || [];
        },

        manageUsersField: function () {
            if (this.model.get('mode') === 'timeline') {
                this.showField('users');
            } else {
                this.hideField('users');
            }
        }

    });
});


