

Core.define('views/user/modals/access', 'views/modal', function (Dep) {

    return Dep.extend({

        cssName: 'user-access',

        multiple: false,

        template: 'user/modals/access',

        header: false,

        data: function () {
            return {
                assignmentPermission: this.options.aclData.assignmentPermission,
                userPermission: this.options.aclData.userPermission,
                portalPermission: this.options.aclData.portalPermission,
                levelListTranslation: this.getLanguage().get('Role', 'options', 'levelList') || {}
            };
        },

        setup: function () {
            this.buttonList = [
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];

            this.createView('table', 'views/role/record/table', {
                acl: {
                    data: this.options.aclData.table,
                    fieldData: this.options.aclData.fieldTable,
                },
                final: true
            });

            this.header = this.translate('Access');
        }

    });
});

