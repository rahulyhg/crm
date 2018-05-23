

Core.define('views/record/panels/default-side', 'views/record/panels/side', function (Dep) {

    return Dep.extend({

        template: 'record/panels/default-side',

        setup: function () {
            Dep.prototype.setup.call(this);
            this.createField('modifiedBy', true);
            this.createField('modifiedAt', true);
            this.createField('createdBy', true);
            this.createField('createdAt', true);

            if (!this.model.get('createdById')) {
                this.recordViewObject.hideField('complexCreated');
            }
            if (!this.model.get('modifiedById')) {
                this.recordViewObject.hideField('complexModified');
            }
            this.listenTo(this.model, 'change:createdById', function () {
                if (!this.model.get('createdById')) return;
                this.recordViewObject.showField('complexCreated');
            }, this);
            this.listenTo(this.model, 'change:modifiedById', function () {
                if (!this.model.get('modifiedById')) return;
                this.recordViewObject.showField('complexModified');
            }, this);

            if (this.getMetadata().get('scopes.' + this.model.name + '.stream')) {
                this.createField('followers', true, 'views/fields/followers');
            }
        },
    });
});

