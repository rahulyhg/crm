

Core.define('crm:controllers/lead', 'controllers/record', function (Dep) {

    return Dep.extend({

        convert: function (id) {
            this.main('crm:views/lead/convert', {
                id: id
            });
        },

    });
});


