/*********************************************************************************
 * The contents of this file are subject to the CoreCRM Advanced
 * Agreement ("License") which can be viewed at
 * http://www.espocrm.com/advanced-pack-agreement.
 * By installing or using this file, You have unconditionally agreed to the
 * terms and conditions of the License, and You may not use this file except in
 * compliance with the License.  Under the terms of the license, You shall not,
 * sublicense, resell, rent, lease, distribute, or otherwise  transfer rights
 * or usage to the software.
 * 
 * License ID: bcac485dee9efd0f36cf6842ad5b69b4
 ***********************************************************************************/

Core.define('Advanced:Views.Workflow.Record.Edit', ['Views.Record.Edit', 'Advanced:Views.Workflow.Record.Detail'], function (Dep, Detail) {

    return Dep.extend({

        bottomView: 'Advanced:Workflow.Record.EditBottom',

        sideView: 'Advanced:Workflow.Record.EditSide',

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            var conditions = {};
            var actions = [];

            var conditionsView = this.getView('bottom').getView('conditions');
            if (conditionsView) {
                conditions = conditionsView.fetch();
            }
            data.conditionsAny = conditions.any || [];
            data.conditionsAll = conditions.all || [];
            data.conditionsFormula = conditions.formula || null;

            var actionsView = this.getView('bottom').getView('actions');
            if (actionsView) {
                actions = actionsView.fetch();
            }

            data.actions = actions;

            return data;
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            Detail.prototype.manageFieldsVisibility.call(this);
            this.listenTo(this.model, 'change', function (model, options) {
                if (this.model.hasChanged('portalOnly') || this.model.hasChanged('type')) {
                    Detail.prototype.manageFieldsVisibility.call(this, options.ui);
                }
            }, this);

            this.listenTo(this.model, 'change:entityType', function (model, value, o) {
                if (o.ui) {
                    setTimeout(function () {
                        model.set({
                            'targetReportId': null,
                            'targetReportName': null
                        });
                    }, 100);
                }
            }, this);

            if (!this.model.isNew()) {
                this.setFieldReadOnly('type');
                this.setFieldReadOnly('entityType');
            }
        }

    });
});

