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

Core.define('Advanced:views/workflow/actions/update-related-entity', 'Advanced:views/workflow/actions/base', function (Dep) {

    return Dep.extend({

        type: 'updateRelatedEntity',

        defaultActionData: {
            link: false,
            fieldList: [],
            fields: {},
        },

        additionalSetup: function() {
            Dep.prototype.additionalSetup.call(this);

            if (this.actionData.link) {
                var linkData = this.getMetadata().get('entityDefs.' + this.entityType + '.links.' + this.actionData.link);

                this.linkedEntityName = linkData.entity || this.entityType;
                this.displayedLinkedEntityName = null;
                if (linkData.type == 'belongsToParent') {
                    this.linkedEntityName = this.actionData.parentEntity || this.linkedEntityName;
                    this.displayedLinkedEntityName = this.translate(this.actionData.link, 'links' , this.entityType) + ' &raquo; ' + this.translate(this.actionData.parentEntity, 'scopeNames');
                }
            }
        }

    });
});

