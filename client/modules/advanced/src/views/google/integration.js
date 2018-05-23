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

Core.define('Advanced:Views.Google.Integration', ['Views.Admin.Integrations.OAuth2', 'Model'], function (Dep, Model) {

    return Dep.extend({

        products: [],
        
        setup: function () {
            this.integration = this.options.integration;

            this.helpText = false;
            if (this.getLanguage().has(this.integration, 'help', 'Integration')) {
                this.helpText = this.translate(this.integration, 'help', 'Integration');
            }

            this.fieldList = [];
            this.fields = [];

            this.dataFieldList = [];

            this.model = new Model();
            this.model.id = this.integration;
            this.model.name = 'Integration';
            this.model.urlRoot = 'Integration';

            this.model.defs = {
                fields: {
                    enabled: {
                        required: true,
                        type: 'bool'
                    },
                }
            };
            
            this.wait(true);
            
            this.fields = this.getMetadata().get('integrations.' + this.integration + '.fields');

            Object.keys(this.fields).forEach(function (name) {
                this.model.defs.fields[name] = this.fields[name];
                this.dataFieldList.push(name);
            }, this);
            this.products = this.getMetadata().get('integrations.' + this.integration + '.products');
            this.model.populateDefaults();

            this.listenToOnce(this.model, 'sync', function () {
                this.createFieldView('bool', 'enabled');
                Object.keys(this.fields).forEach(function (name) {
                    this.createFieldView(this.fields[name]['type'], name, null, this.fields[name]);
                }, this);
                
                this.wait(false);
            }, this);

            this.model.fetch();
        },
        
    });

});
