

Core.define('views/fields/colorpicker', ['views/fields/varchar', 'lib!Colorpicker'], function (Dep, Colorpicker) {

    return Dep.extend({

        type: 'varchar',

        detailTemplate: 'fields/colorpicker/detail',

        listTemplate: 'fields/colorpicker/detail',

        editTemplate: 'fields/colorpicker/edit',

        forceTrim: true,

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (this.mode == 'edit') {
                this.$element.parent().colorpicker({
                    format: 'hex'
                });
            }
        }

    });
});

