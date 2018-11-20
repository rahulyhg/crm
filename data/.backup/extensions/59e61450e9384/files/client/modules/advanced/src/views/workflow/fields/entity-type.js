/*********************************************************************************
 * The contents of this file are subject to the CRM Advanced
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

Core.define('Advanced:Views.Workflow.Fields.EntityType', 'Views.Fields.Enum', function (Dep) {

    return Dep.extend({

        entityListToIgnore: [],

        setup: function () {
            var scopes = this.getMetadata().get('scopes');
            var entityListToIgnore = this.getMetadata().get('entityDefs.Workflow.entityListToIgnore') || [];
            this.params.options = Object.keys(scopes).filter(function (scope) {
                if (~entityListToIgnore.indexOf(scope)) {
                    return;
                }
                var defs = scopes[scope];
                return (defs.entity && (defs.tab || defs.object || defs.workflow));
            }).sort(function (v1, v2) {
                return this.translate(v1, 'scopeNamesPlural').localeCompare(this.translate(v2, 'scopeNamesPlural'));
            }.bind(this));

            this.params.options.unshift('');

            this.params.translation = 'Global.scopeNames';

            Dep.prototype.setup.call(this);
        },

    });

});
