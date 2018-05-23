

Core.define('crm:views/dashlets/options/activities', 'views/dashlets/options/base', function (Dep) {

    return Dep.extend({

        init: function () {
            Dep.prototype.init.call(this);
            this.fields.enabledScopeList.options = this.getConfig().get('activitiesEntityList') || [];
        }

    });
});


