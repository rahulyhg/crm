

Core.define('views/fields/bool', 'views/fields/base', function (Dep) {

    return Dep.extend({

        type: 'bool',

        listTemplate: 'fields/bool/detail',

        detailTemplate: 'fields/bool/detail',

        editTemplate: 'fields/bool/edit',

        searchTemplate: 'fields/bool/search',

        validations: [],

        fetch: function () {
            var value = this.$element.get(0).checked;
            var data = {};
            data[this.name] = value;
            return data;
        },

        fetchSearch: function () {
            var data = {
                type: this.$element.get(0).checked ? 'isTrue' : 'isFalse',
            };
            return data;
        },

        populateSearchDefaults: function () {
            this.$element.get(0).checked = true;
        }
    });
});

