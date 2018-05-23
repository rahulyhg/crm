

Core.define('views/admin/dynamic-logic/conditions/not', 'views/admin/dynamic-logic/conditions/group-base', function (Dep) {

    return Dep.extend({

        template: 'admin/dynamic-logic/conditions/not',

        operator: 'not',

        data: function () {
            return {
                viewKey: this.viewKey,
                operator: this.operator,
                hasItem: this.hasView(this.viewKey)
            };
        },

        setup: function () {
            this.level = this.options.level || 0;
            this.number = this.options.number || 0;
            this.scope = this.options.scope;

            this.itemData = this.options.itemData || {};
            this.viewList = [];

            var i = 0;
            var key = this.getKey();

            this.createItemView(i, key, this.itemData.value);
            this.viewKey = key;
        },

        removeItem: function () {
            var key = this.getKey();
            this.clearView(key);
        },

        getKey: function () {
            var i = 0;
            return 'view-' + this.level.toString() + '-' + this.number.toString() + '-' + i.toString();
        },

        getIndexForNewItem: function () {
            return 0;
        },

        addItemContainer: function () {
        },

        addViewDataListItem: function () {
        },

        fetch: function () {
            var value = this.getView(this.viewKey).fetch();

            return {
                type: this.operator,
                value: value
            };
        },

    });

});

