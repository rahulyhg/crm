

Core.define('views/inbound-email/record/edit', ['views/record/edit', 'views/inbound-email/record/detail'], function (Dep, Detail) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            Detail.prototype.setupFieldsBehaviour.call(this);

            if (Detail.prototype.wasFetched.call(this)) {
                this.setFieldReadOnly('fetchSince');
            }
        },

        controlStatusField: function () {
            Detail.prototype.controlStatusField.call(this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            Detail.prototype.initSslFieldListening.call(this);
        },

    });
});

