

Core.define('crm:views/target-list/record/row-actions/default', 'views/record/row-actions/relationship-no-remove', function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var list = Dep.prototype.getActionList.call(this);
            if (this.options.acl.edit) {
                list.push({
                    action: 'optOut',
                    label: 'Opt-Out',
                    data: {
                        id: this.model.id,
                        type: this.model.name
                    }
                });
            }
            return list;
        }
    });
});

