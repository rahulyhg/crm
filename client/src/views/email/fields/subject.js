
Core.define('views/email/fields/subject', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        listLinkTemplate: 'email/fields/subject/list-link',

        data: function () {
            return _.extend({
                'isRead': (this.model.get('sentById') === this.getUser().id) || this.model.get('isRead'),
                'isImportant': this.model.get('isImportant'),
                'hasAttachment': this.model.get('hasAttachment')
            }, Dep.prototype.data.call(this));
        },

        getValueForDisplay: function () {
            return this.model.get('name');
        },

        getAttributeList: function () {
            return ['name', 'isRead', 'isImportant'];
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            this.listenTo(this.model, 'change', function () {
                if (this.mode == 'list' || this.mode == 'listLink') {
                    if (this.model.hasChanged('isRead') || this.model.hasChanged('isImportant')) {
                        this.reRender();
                    }
                }
            }, this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
        },

    });

});
