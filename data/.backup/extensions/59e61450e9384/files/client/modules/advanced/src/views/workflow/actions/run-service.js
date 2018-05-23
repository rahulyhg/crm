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

Core.define('Advanced:Views.Workflow.Actions.RunService', ['Advanced:Views.Workflow.Actions.Base', 'Model'], function (Dep, Model) {

    return Dep.extend({

        type: 'runService',

        template: 'advanced:workflow.actions.run-service',

        data: function () {
            return _.extend({
                "methodName": this.translatedOption,
                "additionalParameters": this.actionData.additionalParameters
            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            var methodName = this.actionData.methodName || null;

            var model = new Model();
            model.name = 'Workflow';
            model.set({
                methodName: methodName,
                additionalParameters: this.actionData.additionalParameters
            });

            this.translatedOption = methodName;
            if (methodName) {
                this.translatedOption = this.translate(methodName, 'serviceActions', 'Workflow');

                var labelName = this.entityType + methodName.charAt(0).toUpperCase() + methodName.slice(1);
                if (this.getLanguage().has(labelName, 'serviceActions', 'Workflow')) {
                    this.translatedOption = this.translate(labelName, 'serviceActions', 'Workflow');
                }
            }
        }

    });
});

