

Core.define('views/admin/field-manager/fields/options', 'views/fields/array', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.translatedOptions = {};
            var list = this.model.get(this.name) || [];
            list.forEach(function (value) {
                this.translatedOptions[value] = this.getLanguage().translateOption(value, this.options.field, this.options.scope);
            }, this);
        },

        getItemHtml: function (value) {
            var valueSanitized = this.getHelper().stripTags(value);
            var translatedValue = this.translatedOptions[value] || valueSanitized;

            var valueSanitized = valueSanitized.replace(/"/g, '&quot;');

            var html = '' +
            '<div class="list-group-item link-with-role form-inline" data-value="' + valueSanitized + '">' +
                '<div class="pull-left" style="width: 92%; display: inline-block;">' +
                    '<input name="translatedValue" data-value="' + valueSanitized + '" class="role form-control input-sm pull-right" value="'+translatedValue+'">' +
                    '<div>' + valueSanitized + '</div>' +
                '</div>' +
                '<div style="width: 8%; display: inline-block; vertical-align: top;">' +
                    '<a href="javascript:" class="pull-right" data-value="' + valueSanitized + '" data-action="removeValue"><span class="glyphicon glyphicon-remove"></a>' +
                '</div><br style="clear: both;" />' +
            '</div>';

            return html;
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            if (!data[this.name].length) {
                data[this.name] = false;
                data.translatedOptions = {};
                return;
            }

            data.translatedOptions = {};
            (data[this.name] || []).forEach(function (value) {
                valueSanitized = this.getHelper().stripTags(value).replace(/"/g, '&quot;');

                data.translatedOptions[value] = this.$el.find('input[name="translatedValue"][data-value="'+valueSanitized+'"]').val() || value;
                data.translatedOptions[value] = data.translatedOptions[value].toString();
            }, this);

            return data;
        }

    });

});
