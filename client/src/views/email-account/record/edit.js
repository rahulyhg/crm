

Core.define('views/email-account/record/edit', ['views/record/edit', 'views/email-account/record/detail'], function (Dep, Detail) {

    return Dep.extend({

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            Detail.prototype.initSslFieldListening.call(this);
            Detail.prototype.initSmtpFieldsControl.call(this);

            if (Detail.prototype.wasFetched.call(this)) {
                this.setFieldReadOnly('fetchSince');
            }
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            if (this.getUser().isAdmin()) {
                this.setFieldNotReadOnly('assignedUser');
            } else {
                this.setFieldReadOnly('assignedUser');
            }
        },

        controlSmtpFields: function () {
            Detail.prototype.controlSmtpFields.call(this);
        },

        controlSmtpAuthField: function () {
            Detail.prototype.controlSmtpAuthField.call(this);
        }

    });

});

