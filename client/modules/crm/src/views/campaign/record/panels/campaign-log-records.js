


Core.define('crm:views/campaign/record/panels/campaign-log-records', 'views/record/panels/relationship', function (Dep) {

    return Dep.extend({

    	filterList: ["all", "sent", "opened", "optedOut", "bounced", "clicked", "leadCreated"],

    	data: function () {
    		return _.extend({
    			filterList: this.filterList,
    			filterValue: this.filterValue
    		}, Dep.prototype.data.call(this));
    	},

    	setup: function () {
            if (this.getAcl().checkScope('TargetList', 'create')) {
                this.actionList.push({
                    action: 'createTargetList',
                    label: 'Create Target List'
                });
            }
    		Dep.prototype.setup.call(this);
    	},

        actionCreateTargetList: function () {
            var attributes = {
                sourceCampaignId: this.model.id,
                sourceCampaignName: this.model.get('name')
            };

            if (!this.collection.data.primaryFilter) {
                attributes.includingActionList = [];
            } else {
                var status = Core.Utils.upperCaseFirst(this.collection.data.primaryFilter).replace(/([A-Z])/g, ' $1');
                attributes.includingActionList = [status];
            }

            var viewName = this.getMetadata().get('clientDefs.TargetList.modalViews.edit') || 'views/modals/edit';
            this.createView('quickCreate', viewName, {
                scope: 'TargetList',
                attributes: attributes,
                fullFormDisabled: true,
                layoutName: 'createFromCampaignLog'
            }, function (view) {
                view.render();
                this.listenToOnce(view, 'after:save', function () {
                    Core.Ui.success(this.translate('Done'));
                }, this);
            }, this);
        }

    });
});


