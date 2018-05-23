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

Core.define('Advanced:Views.Campaign.Modals.MailChimp', 'Advanced:Views.Modals.MailChimpBase', function (Dep) {

    return Dep.extend({
    
        foreignEntity: 'Campaign',
        hasFooter: false,
        footerView: 'Advanced:Campaign.Modals.MailChimpFooter',
        
        setup: function () {
            this.header = this.translate('MailChimp Campaign Sync', 'labels','Campaign');
            Dep.prototype.setup.call(this);
	    },
	    
	    childSetup: function () {
            this.model.defs = {
				    fields: {
					    mailChimpCampaign: {
						    type: 'base',
						    entity: 'MailChimpCampaign',
						    view:'Advanced:MailChimp.Fields.MailChimpLink',
					    },
				    },
			    };
			    this.createFieldView('base', 'Advanced:MailChimp.Fields.MailChimpCampaignLink', 'mailChimpCampaign', false);
			    this.fieldData.mailChimpCampaign = {
			        parentType: 'Campaign',
			        parentId: this.options.model.id,
			        parentName: this.options.model.get('name'),
			        parentLabel: this.translate('Core Campaign', 'labels','MailChimp'),
			        label: this.translate('MailChimp Campaign', 'labels','MailChimp')  
			    };
			    
			    var targetListsIds = this.model.get('targetListsIds');
			    for(targetListIdIdx in targetListsIds) {
			        
			        targetListId = targetListsIds[targetListIdIdx];
			        
			        this.model.defs.fields[targetListId+'_mailChimpList'] = {
						type: 'base',
						entity: 'MailChimpList',
						view:'Advanced:MailChimp.Fields.MailChimpLink',
					};
					this.model.defs.fields[targetListId+'_mcListGroup'] = {
						type: 'base',
						entity: 'MailChimpListGroup',
						customTooltip: true,
						tooltipContentLabel: 'mailChimpGroup',
						view: 'Advanced:MailChimp.Fields.GroupLinkTree',
					};
                    this.fieldData[targetListId+'_mailChimpList'] = {
			            parentType: 'TargetList',
			            parentId: targetListId,
			            parentName: this.model.get(targetListId+'_name'),
			            parentLabel: this.translate('Core TargetList', 'labels','MailChimp'),
			            label: this.translate('MailChimp TargetList', 'labels','MailChimp')
			        };
			        
			        this.fieldData[targetListId+'_mcListGroup'] = {
			            parentType: 'TargetList',
                        parentId: targetListId,
                        parentName: '',
                        parentLabel: '',
                        listField: targetListId+'_mailChimpList',
                        label: this.translate('MailChimp TargetListGroup', 'labels','MailChimp')
			        };
			        
					this.createFieldView('base', 'Advanced:MailChimp.Fields.MailChimpLink', targetListId+'_mailChimpList', false);
					this.createFieldView('base', 'Advanced:MailChimp.Fields.GroupLinkTree', targetListId+'_mcListGroup', false, {listField: targetListId+'_mailChimpList'});
			    }
        },

    });
});

