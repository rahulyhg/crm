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

Core.define('advanced:views/workflow/action-modals/apply-assignment-rule', ['advanced:views/workflow/action-modals/base', 'model'], function (Dep, Model) {

    return Dep.extend({

        template: 'advanced:workflow/action-modals/apply-assignment-rule',

        data: function () {
            return _.extend({

            }, Dep.prototype.data.call(this));
        },

        events: {
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            var model = new Model();
            model.name = 'Workflow';

            this.actionModel = model;

            model.set({
                assignmentRule: this.actionData.assignmentRule,
                targetTeamId: this.actionData.targetTeamId,
                targetTeamName: this.actionData.targetTeamName,
                targetUserPosition: this.actionData.targetUserPosition,
                listReportId: this.actionData.listReportId,
                listReportName: this.actionData.listReportName
            });

            this.createView('assignmentRule', 'views/fields/enum', {
                mode: 'edit',
                model: model,
                el: this.options.el + ' .field[data-name="assignmentRule"]',
                defs: {
                    name: 'assignmentRule',
                    params: {
                        options: this.getMetadata().get('entityDefs.Workflow.assignmentRuleList') || []
                    }
                },
                readOnly: this.readOnly
            });

            this.createView('targetTeam', 'views/fields/link', {
                mode: 'edit',
                model: model,
                el: this.options.el + ' .field[data-name="targetTeam"]',
                foreignScope: 'Team',
                defs: {
                    name: 'targetTeam',
                    params: {
                        required: true
                    }
                },
                readOnly: this.readOnly
            });

            this.createView('targetUserPosition', 'advanced:views/workflow/fields/target-user-position', {
                mode: 'edit',
                model: model,
                el: this.options.el + ' .field[data-name="targetUserPosition"]',
                defs: {
                    name: 'targetUserPosition'
                },
                readOnly: this.readOnly
            });

            this.createView('listReport', 'advanced:views/workflow/fields/list-report', {
                mode: 'edit',
                model: model,
                el: this.options.el + ' .field[data-name="listReport"]',
                entityType: this.options.entityType,
                foreignScope: 'Report',
                defs: {
                    name: 'listReport'
                },
                readOnly: this.readOnly
            });
        },

        fetch: function () {
            var actionModel = this.actionModel;

            this.getView('assignmentRule').fetchToModel();
            this.getView('targetTeam').fetchToModel();
            this.getView('targetUserPosition').fetchToModel();
            this.getView('listReport').fetchToModel();

            var isNotValid = false;

            isNotValid = this.getView('assignmentRule').validate() || isNotValid;
            isNotValid = this.getView('targetTeam').validate() || isNotValid;
            isNotValid = this.getView('targetUserPosition').validate() || isNotValid;
            isNotValid = this.getView('listReport').validate() || isNotValid;
            if (isNotValid) return;

            this.actionData.assignmentRule = actionModel.get('assignmentRule');
            this.actionData.targetTeamId = actionModel.get('targetTeamId');
            this.actionData.targetTeamName = actionModel.get('targetTeamName');
            this.actionData.targetUserPosition = actionModel.get('targetUserPosition');
            this.actionData.listReportId = actionModel.get('listReportId');
            this.actionData.listReportName = actionModel.get('listReportName');

            return true;
        },


    });
});
