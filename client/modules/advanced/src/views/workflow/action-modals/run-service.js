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

Core.define('Advanced:Views.Workflow.ActionModals.RunService', ['Advanced:Views.Workflow.ActionModals.Base', 'Model'], function (Dep, Model) {

    return Dep.extend({

        template: 'advanced:workflow.action-modals.run-service',

        data: function () {
            return _.extend({

            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            var model = new Model();
            model.name = 'Workflow';
            model.set({
                methodName: this.actionData.methodName,
                additionalParameters: this.actionData.additionalParameters
            });

            var methodOptionList = [''];
            var translatedOptions = {};

            var actionsData = this.getMetadata().get('entityDefs.Workflow.serviceActions.' + this.entityType);

            var addMethodName = function (methodName) {
                methodOptionList.push(methodName);

                translatedOptions[methodName] = this.translate(methodName, 'serviceActions', 'Workflow');

                var labelName = this.entityType + methodName.charAt(0).toUpperCase() + methodName.slice(1);
                if (this.getLanguage().has(labelName, 'serviceActions', 'Workflow')) {
                    translatedOptions[methodName] = this.translate(labelName, 'serviceActions', 'Workflow');
                }
            }.bind(this);

            if (actionsData && Array.isArray(actionsData)) {
                actionsData.forEach(function(methodName) {
                    addMethodName(methodName);
                }.bind(this));
            } else if (actionsData) {
                for (var methodName in actionsData) {
                    addMethodName(methodName);
                }
            }

            this.createView('methodName', 'Fields.Enum', {
                mode: 'edit',
                model: model,
                el: this.options.el + ' .field-methodName',
                defs: {
                    name: 'methodName',
                    params: {
                        options: methodOptionList,
                        required: true,
                        translatedOptions: translatedOptions
                    }
                },
                readOnly: this.readOnly
            });

            this.createView('additionalParameters', 'Fields.Text', {
                mode: 'edit',
                model: model,
                el: this.options.el + ' .field-additionalParameters',
                defs: {
                    name: 'additionalParameters'
                },
                readOnly: this.readOnly
            });
        },

        fetch: function () {
            this.getView('methodName').fetchToModel();
            if (this.getView('methodName').validate()) {
                return;
            }

            this.actionData.methodName = (this.getView('methodName').fetch()).methodName;
            this.actionData.additionalParameters = (this.getView('additionalParameters').fetch()).additionalParameters;

            return true;
        },

    });
});
