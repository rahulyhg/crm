/*********************************************************************************
 * The contents of this file are subject to the Samex CRM Advanced
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

Core.define('advanced:views/workflow/actions/apply-assignment-rule', ['advanced:views/workflow/actions/base', 'model'], function (Dep, Model) {

    return Dep.extend({

        template: 'advanced:workflow/actions/apply-assignment-rule',

        type: 'applyAssignmentRule',

        defaultActionData: {
            assignmentRule: 'Round-Robin',
            targetTeamId: null,
            targetTeamName: null,
            targetUserPosition: null,
            listReportId: null,
            listReportName: null
        },

        data: function () {
            var data = Dep.prototype.data.call(this);
            return data;
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            var model = new Model();
            model.name = 'Workflow';
            model.set({
                assignmentRule: this.actionData.assignmentRule,
                targetTeamId: this.actionData.targetTeamId,
                targetTeamName: this.actionData.targetTeamName,
                targetUserPosition: this.actionData.targetUserPosition,
                listReportId: this.actionData.listReportId,
                listReportName: this.actionData.listReportName
            });

            this.createView('assignmentRule', 'views/fields/enum', {
                mode: 'detail',
                model: model,
                el: this.options.el + ' .field[data-name="assignmentRule"]',
                defs: {
                    name: 'assignmentRule',
                    params: {
                        options: this.getMetadata().get('entityDefs.Workflow.assignmentRuleList') || []
                    }
                },
                readOnly: true
            }, function (view) {
                view.render();
            });

            this.createView('targetTeam', 'views/fields/link', {
                mode: 'detail',
                model: model,
                el: this.options.el + ' .field[data-name="targetTeam"]',
                foreignScope: 'Team',
                defs: {
                    name: 'targetTeam'
                },
                readOnly: true
            }, function (view) {
                view.render();
            });

            this.createView('targetUserPosition', 'advanced:views/workflow/fields/target-user-position', {
                mode: 'detail',
                model: model,
                el: this.options.el + ' .field[data-name="targetUserPosition"]',
                foreignScope: 'Report',
                defs: {
                    name: 'targetUserPosition'
                },
                readOnly: true
            }, function (view) {
                view.render();
            });

            this.createView('listReport', 'advanced:views/workflow/fields/list-report', {
                mode: 'detail',
                model: model,
                el: this.options.el + ' .field[data-name="listReport"]',
                foreignScope: 'Report',
                entityType: this.model.get('entityType'),
                defs: {
                    name: 'listReport'
                },
                readOnly: true
            }, function (view) {
                view.render();
            });

        }

    });
});

