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

Core.define('Advanced:Views.TargetList.Modals.MailChimp', 'Advanced:Views.Modals.MailChimpBase', function (Dep) {

    return Dep.extend({
    
        foreignEntity: 'TargetList',

        setup: function () {
            Dep.prototype.setup.call(this);
            this.header = this.translate('MailChimp List Sync', 'labels', 'TargetList');
        },

        childSetup: function () {
            this.model.defs = {
                fields: {
                    mailChimpList: {
                        type: 'base',
                        entity: 'MailChimpList',
                        view:'Advanced:MailChimp.Fields.MailChimpLink',
                    },
                    mcListGroup: {
                        type: 'base',
                        entity: 'MailChimpListGroup',
                        customTooltip: true,
						tooltipContentLabel: 'mailChimpGroup',
                        view:'Advanced:MailChimp.Fields.GroupLinkTree',
                    },
                },
            };
            this.createFieldView('base', 'Advanced:MailChimp.Fields.MailChimpLink', 'mailChimpList', false);
            this.createFieldView('base', 'Advanced:MailChimp.Fields.GroupLinkTree', 'mcListGroup', false,{listField:'mailChimpList'});
            
            this.fieldData.mailChimpList = {
                parentType: 'TargetList',
                parentId: this.options.model.id,
                parentName: this.options.model.get('name'),
                parentLabel: this.translate('Core TargetList', 'labels','MailChimp'),
                label: this.translate('MailChimp TargetList', 'labels','MailChimp')
            };
            
            this.fieldData.mcListGroup = {
                parentType: 'TargetList',
                parentId: this.options.model.id,
                parentName: '',
                parentLabel: '',
                listField: 'mailChimpList',
                label: this.translate('MailChimp TargetListGroup', 'labels','MailChimp')
            };
            
            
        }, 
        
    });
});
