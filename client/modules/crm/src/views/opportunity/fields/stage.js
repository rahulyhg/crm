

Core.define('crm:views/opportunity/fields/stage', 'views/fields/enum', function (Dep) {

    return Dep.extend({

        listTemplate: 'fields/enum-styled/detail',

        detailTemplate: 'fields/enum-styled/detail',

        data: function () {
            var style = 'default';
            var stage = this.model.get('stage');
            if (stage == 'Closed Won') {
                style = 'success';
            } else if (stage == 'Closed Lost') {
                style = 'danger';
            }
            return _.extend({
                style: style,
            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.probabilityMap = this.getMetadata().get('entityDefs.Opportunity.fields.stage.probabilityMap') || {};

            if (this.mode != 'list') {
                this.on('change', function () {
                    this.model.set('probability', this.probabilityMap[this.model.get(this.name)]);
                }, this);
            }
        }

    });

});
