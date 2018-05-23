

Core.define('crm:views/knowledge-base-article/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.getAcl().checkScope('Email', 'create')) {
                this.dropdownItemList.push({
                    'label': 'Send in Email',
                    'name': 'sendInEmail'
                });
            }
        },

        actionSendInEmail: function () {
            Core.Ui.notify(this.translate('pleaseWait', 'messages'));
            Core.require('crm:knowledge-base-helper', function (Helper) {
                var helper = new Helper(this.getLanguage());

                helper.getAttributesForEmail(this.model, {}, function (attributes) {
                    var viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') || 'views/modals/compose-email';
                    this.createView('composeEmail', viewName, {
                        attributes: attributes,
                        selectTemplateDisabled: true,
                        signatureDisabled: true
                    }, function (view) {
                        Core.Ui.notify(false);
                        view.render();
                    }, this);
                }.bind(this));
            }, this);
        },

    });
});

