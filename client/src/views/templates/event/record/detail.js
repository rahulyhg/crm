

Core.define('views/templates/event/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            if (this.getAcl().checkModel(this.model, 'edit')) {
                if (['Held', 'Not Held'].indexOf(this.model.get('status')) == -1) {
                    this.dropdownItemList.push({
                        'html': this.translate('Set Held', 'labels', 'Meeting'),
                        'name': 'setHeld'
                    });
                    this.dropdownItemList.push({
                        'html': this.translate('Set Not Held', 'labels', 'Meeting'),
                        'name': 'setNotHeld'
                    });
                }
            }
            console.log(this.dropdownItemList);
        },

        actionSetHeld: function () {
                this.model.save({
                    status: 'Held'
                }, {
                    patch: true,
                    success: function () {
                        Core.Ui.success(this.translate('Saved', 'labels', 'Meeting'));
                        this.removeButton('setHeld');
                        this.removeButton('setNotHeld');
                    }.bind(this),
                });
        },

        actionSetNotHeld: function () {
                this.model.save({
                    status: 'Not Held'
                }, {
                    patch: true,
                    success: function () {
                        Core.Ui.success(this.translate('Saved', 'labels', 'Meeting'));
                        this.removeButton('setHeld');
                        this.removeButton('setNotHeld');
                    }.bind(this),
                });
        },

    });
});

