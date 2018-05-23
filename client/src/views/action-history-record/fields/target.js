

Core.define('views/action-history-record/fields/target', 'views/fields/link-parent', function (Dep) {

    return Dep.extend({

        ignoreScopeList: ['Preferences', 'ExternalAccount', 'Notification', 'Note'],

        setup: function () {
            Dep.prototype.setup.call(this);

            var scopes = this.getMetadata().get('scopes') || {};
            this.foreignScopeList = this.getMetadata().getScopeEntityList().filter(function (item) {

                if (!this.getUser().isAdmin()) {
                    if (!this.getAcl().checkScopeHasAcl(item)) return;
                }
                if (~this.ignoreScopeList.indexOf(item)) return;

                if (!this.getAcl().checkScope(item)) return;

                return true;
            }, this);

            this.getLanguage().sortEntityList(this.foreignScopeList);

        }

    });
});
