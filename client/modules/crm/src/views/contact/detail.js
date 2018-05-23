

Core.define('crm:views/contact/detail', 'views/detail', function (Dep) {

    return Dep.extend({

        relatedAttributeMap: {
            'opportunities': {
                'accountId': 'accountId',
                'accountName': 'accountName'
            },
            'cases': {
                'accountId': 'accountId',
                'accountName': 'accountName'
            }
        },

        selectRelatedFilters: {
            'cases': {
                'account': function () {
                    if (this.model.get('accountId')) {
                        return {
                            field: 'accountId',
                            type: 'equals',
                            value: this.model.get('accountId'),
                            valueName: this.model.get('accountName')
                        };
                    }
                }
            },
            'opportunities': {
                'account': function () {
                    if (this.model.get('accountId')) {
                        return {
                            field: 'accountId',
                            type: 'equals',
                            value: this.model.get('accountId'),
                            valueName: this.model.get('accountName')
                        };
                    }
                }
            }
        }

    });
});

