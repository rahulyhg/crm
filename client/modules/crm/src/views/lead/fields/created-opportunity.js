

Core.define('crm:views/lead/fields/created-opportunity', 'views/fields/link', function (Dep) {

    return Dep.extend({

        getSelectFilters: function () {
            if (this.model.get('createdAccountId')) {
                return {
                    'account': {
                        type: 'equals',
                        field: 'accountId',
                        value: this.model.get('createdAccountId'),
                        valueName: this.model.get('createdAccountName')
                    }
                };
            }
        },
    });

});
