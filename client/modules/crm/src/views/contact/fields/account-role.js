

Core.define('crm:views/contact/fields/account-role', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        detailTemplate: 'crm:contact/fields/account-role/detail',

        listTemplate: 'crm:contact/fields/account-role/detail',

        data: function () {
            var data = Dep.prototype.data.call(this);

            if (this.model.has('accountIsInactive')) {
                data.accountIsInactive = this.model.get('accountIsInactive');
            }
            return data;
        }
    });

});
