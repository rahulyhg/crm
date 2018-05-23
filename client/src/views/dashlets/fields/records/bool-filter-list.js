
Core.define('views/dashlets/fields/records/bool-filter-list', 'views/fields/multi-enum', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:entityType', function () {
                this.setupOptions();
                this.reRender();
            }, this);
        },

        setupOptions: function () {
            var entityType = this.model.get('entityType');
            if (!entityType) {
                this.params.options = [];
                return;
            }
            this.params.options = this.getMetadata().get(['clientDefs', entityType, 'boolFilterList']) || [];

            if (this.getMetadata().get(['scopes', entityType, 'stream']) && this.getAcl().checkScope(entityType, 'stream')) {
                this.params.options.push('followed');
            }

            this.translatedOptions = {};
            this.params.options.forEach(function (item) {
                this.translatedOptions[item] = this.translate(item, 'boolFilters', entityType);
            }, this);
        }

    });

});
