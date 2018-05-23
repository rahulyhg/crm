Core.define('advanced:views/mail-chimp/custom-merge-field', ['View', 'Model'], function (Dep, Model) {

    return Dep.extend({
        template: 'advanced:mail-chimp.custom-merge-field',
        
        scopeList: ['Contact', 'Account', 'Lead', 'User'],
        mergeFieldData: {},
        
        data: function () {
            return {
                readOnly: this.readOnly,
                mergeFieldData: this.mergeFieldData
            };
        },

        events: {
            'click [data-action="editMergeField"]': function () {
                this.edit();
            }
        },

        setup: function () {
            this.id = this.options.id;
            this.readOnly = this.options.readOnly;

            this.mergeFieldData = this.options.mergeFieldData || {};
        },

        edit: function (isNew) {
            this.createView('edit', 'Advanced:MailChimp.Modals.CustomMergeField', {
                scopeList: this.scopeList,
                mergeFieldData: this.mergeFieldData,
            }, function (view) {
                view.render();
                if (isNew) {
                    this.listenToOnce(view, 'cancel', function () {
                        setTimeout(function () {
                            this.getParentView().removeMergeField(this.id);
                        }.bind(this), 200);
                    }, this);
                    this.listenToOnce(view, 'close', function () {
                        if (view.mergeFieldData.mergeFieldTag == undefined) {
                            setTimeout(function () {
                                this.getParentView().removeMergeField(this.id);
                            }.bind(this), 200);
                        }
                    }, this);
                }

                this.listenToOnce(view, 'apply', function (data) {
                    this.clearView('edit');
                    this.mergeFieldData = data;
                    setTimeout(function(){
                        this.reRender();
                    }.bind(this), 200);
                }, this);

            }.bind(this));
        },

        fetch: function () {
            return this.mergeFieldData;
        },

  })
});
