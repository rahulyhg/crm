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

Core.define('advanced:views/mail-chimp/fields/custom-merge-fields', 'views/fields/base', function (Dep) {

    return Dep.extend({
        editTemplate: 'advanced:mail-chimp.fields.custom-merge-fields.edit',
        
       
        events: {
            'click [data-action="addMergeField"]': function (e) {
                var $target = $(e.currentTarget);
                this.addMergeField(null, true);
            },
            'click [data-action="removeMergeField"]': function (e) {
                if (confirm(this.translate('Are you sure?'))) {
                    var $target = $(e.currentTarget);
                    var id = $target.data('id');
                    this.removeMergeField(id);
                }
            }
        },

        data: function () {
            return {
                readOnly: this.readOnly
            }
        },

        removeMergeField: function (id)    {
            var $target = this.$el.find('[data-id="' + id + '"]');
            this.clearView('merge-field-' + id);
            $target.parent().remove();
        },

        setup: function () {
            this.readOnly = this.options.readOnly || false;
            this.lastCid = 0;
        },

        cloneData: function (data) {
            data = Core.Utils.clone(data);

            if (Core.Utils.isObject(data) || _.isArray(data)) {
                for (var i in data) {
                    data[i] = this.cloneData(data[i]);
                }
            }
            return data;
        },

        afterRender: function () {
            var mergeFields = Core.Utils.clone(this.model.get(this.name) || []);
            mergeFields.forEach( function (data) {
                this.addMergeField(this.cloneData(data));
            }, this);

        },

        addMergeField: function (data, isNew) {
            data = data || {};

            var $container = this.$el.find('.customMergeField');

            var id = data.cid = this.lastCid;
            this.lastCid++;

            var removeLinkHtml = this.readOnly ? '' : '<a href="javascript:" class="pull-right" data-action="removeMergeField" data-id="'+id+'"><span class="glyphicon glyphicon-remove"></span></a>';

            var html = '<div class="margin clearfix form-control" style="height:inherit !important;">' + removeLinkHtml + '<div class="mailchimp-mergeField" data-id="' + id + '"></div></div>';
            $container.append($(html));

            this.createView('mergeField-' + id, 'Advanced:MailChimp.CustomMergeField', {
                el: this.options.el + ' .mailchimp-mergeField[data-id="' + id + '"]',
                mergeFieldData: data,
                model: this.model,
                id: id,
                isNew: isNew,
                readOnly: this.readOnly
            }, function (view) {
                view.render(function () {
                    if (isNew) {
                        view.edit(true);
                    }
                });
            });
        },

        fetch: function () {
            var data = {};
            var mergeFields = [];
            
            this.$el.find('.customMergeField .mailchimp-mergeField').each(function (index, el) {
                var mergeFieldId = $(el).attr('data-id');
                if (~mergeFieldId) {
                    var view = this.getView('mergeField-' + mergeFieldId);
                    if (view) {
                        mergeFields.push(view.fetch());
                    }
                }
            }.bind(this));
            data[this.name] = mergeFields;
            return data;
        },
        
        validate: function () {
            var tagList = [];
            var invalid = false;
            var mergeFields = this.fetch();
            mergeFields[this.name].forEach( function (mergeFieldData) {
                if (tagList.indexOf(mergeFieldData.mergeFieldTag) == -1) {
                    tagList.push(mergeFieldData.mergeFieldTag);
                } else {
                    var selector = '.customMergeField .mailchimp-mergeField[data-id="'+mergeFieldData.cid+'"]';
                    this.showValidationMessage(this.translate('Duplicated Tag', 'messages', 'MailChimp'), selector);
                    invalid |= true;
                }
            }, this);
            return invalid;
        },
        
    });
});

