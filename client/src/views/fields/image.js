

Core.define('views/fields/image', 'views/fields/file', function (Dep) {

    return Dep.extend({

        type: 'image',

        showPreview: true,

        accept: ['image/*'],

        defaultType: 'image/jpeg',

        previewSize: 'small',

        setup: function () {
            Dep.prototype.setup.call(this);

            if ('previewSize' in this.params && this.params.previewSize) {
                this.previewSize = this.params.previewSize;
            }
        }

    });
});

