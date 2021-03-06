

Core.define('crm:views/call/record/row-actions/default', 'views/record/row-actions/default', function (Dep) {

    return Dep.extend({

        getActionList: function () {
            var actions = Dep.prototype.getActionList.call(this);

            if (this.options.acl.edit && !~['Held', 'Not Held'].indexOf(this.model.get('status'))) {
                actions.push({
                    action: 'setHeld',
                    label: 'Set Held',
                    data: {
                        id: this.model.id
                    }
                });
                actions.push({
                    action: 'setNotHeld',
                    label: 'Set Not Held',
                    data: {
                        id: this.model.id
                    }
                });
            }

            return actions;
        },
    });

});
