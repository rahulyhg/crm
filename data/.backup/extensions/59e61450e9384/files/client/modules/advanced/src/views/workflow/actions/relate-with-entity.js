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

Core.define('advanced:views/workflow/actions/relate-with-entity', ['advanced:views/workflow/actions/base', 'model'], function (Dep, Model) {

    return Dep.extend({

        template: 'advanced:workflow/actions/relate-with-entity',

        type: 'relateWithEntity',

        data: function () {
            var data = Dep.prototype.data.call(this);

            data.linkTranslated = this.translate(this.actionData.link, 'links', this.entityType);
            return data;
        },

        defaultActionData: {
            link: null
        },

        additionalSetup: function() {
            Dep.prototype.additionalSetup.call(this);

            if (this.actionData.link) {
                this.foreignEntityType = this.getMetadata().get('entityDefs.' + this.entityType + '.links.' + this.actionData.link + '.entity');
            }

            this.model = new Model();

            this.model.set({
                entityId: this.actionData.entityId,
                entityName: this.actionData.entityName
            });
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.actionData.entityId) {
                this.createView('entity', 'views/fields/link', {
                    el: this.getSelector() + ' .field[data-name="entity"]',
                    foreignScope: this.foreignEntityType,
                    name: 'entity',
                    model: this.model,
                    mode: 'detail',
                    readOnly: true
                }, function (view) {
                    view.render();
                }, this);
            }
        }

    });
});

