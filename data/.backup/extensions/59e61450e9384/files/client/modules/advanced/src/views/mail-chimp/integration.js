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

Core.define('Advanced:Views.MailChimp.Integration', 'Views.Admin.Integrations.Edit', function (Dep) {

    return Dep.extend({

        createFieldView: function (type, name, readOnly, params) {
            var viewName = this.getFieldManager().getViewName(type);
            if (params && params.view) {
                viewName = params.view;
            }
            this.createView(name, viewName, {
                model: this.model,
                el: this.options.el + ' .field[data-name="'+name+'"]',
                defs: {
                    name: name,
                    params: params
                },
                mode: readOnly ? 'detail' : 'edit',
                readOnly: readOnly,
            });
            this.fieldList.push(name);
        },

    });

});
