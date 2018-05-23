

Core.define('views/dashlets/abstract/base', 'view', function (Dep) {

    return Dep.extend({

        optionsData: null,

        actionRefresh: function () {
            this.render();
        },

        actionOptions: function () {},

        optionsFields: {
            "title": {
                "type": "varchar",
                "required": true
            },
            "autorefreshInterval": {
                "type": "enumFloat",
                "options": [0, 0.5, 1, 2, 5, 10]
            }
        },

        disabledForReadOnlyActionList: ['options', 'remove'],

        init: function () {
            this.name = this.options.name || this.name;
            this.id = this.options.id;

            this.defaultOptions = this.getMetadata().get(['dashlets', this.name, 'options', 'defaults']) || this.defaultOptions || {};

            this.defaultOptions = _.extend({
                title: this.getLanguage().translate(this.name, 'dashlets'),
            }, this.defaultOptions);

            this.defaultOptions = Core.Utils.clone(this.defaultOptions);

            this.optionsFields = this.getMetadata().get(['dashlets', this.name, 'options', 'fields']) || this.optionsFields || {};
            this.optionsFields = Core.Utils.clone(this.optionsFields);

            this.setupDefaultOptions();

            var options = Core.Utils.cloneDeep(this.defaultOptions);

            for (var key in options) {
                if (typeof options[key] == 'function') {
                    options[key] = options[key].call(this);
                }
            }

            if (!this.options.readOnly) {
                var storedOptions = this.getPreferences().getDashletOptions(this.id) || {};
            } else {
                var storedOptions = (this.getConfig().get('dashletsOptions') || {})[this.id] || {};
            }

            this.optionsData = _.extend(options, storedOptions);

            if (this.optionsData.autorefreshInterval || false) {
                var interval = this.optionsData.autorefreshInterval * 60000;

                var t;
                var process = function () {
                    t = setTimeout(function () {
                        this.actionRefresh();
                        process();
                    }.bind(this), interval);
                }.bind(this);

                process();

                this.once('remove', function () {
                    clearTimeout(t);
                }, this);
            }

            this.actionList = Core.Utils.clone(this.actionList);

            if (this.options.readOnly) {
                this.actionList = this.actionList.filter(function(item) {
                    if (~this.disabledForReadOnlyActionList.indexOf(item.name)) {
                        return false;
                    }
                    return true;
                }, this)
            }

            this.setupActionList();
        },

        actionList: [
            {
                name: 'refresh',
                label: 'Refresh',
                iconHtml: '<span class="glyphicon glyphicon-refresh"></span>',
            },
            {
                name: 'options',
                label: 'Options',
                iconHtml: '<span class="glyphicon glyphicon-pencil"></span>',
            },
            {
                name: 'remove',
                label: 'Remove',
                iconHtml: '<span class="glyphicon glyphicon-remove"></span>',
            }
        ],

        setupDefaultOptions: function () {},

        setupActionList: function () {},

        getOption: function (key) {
            return this.optionsData[key];
        }

    });
});


