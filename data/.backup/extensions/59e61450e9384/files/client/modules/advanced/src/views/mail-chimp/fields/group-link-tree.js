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

Core.define('Advanced:Views.MailChimp.Fields.GroupLinkTree', 'Views.Fields.LinkCategoryTree', function (Dep) {

    return Dep.extend({

        editTemplate: 'advanced:mail-chimp.fields.group-link-tree.edit',
        parentIdName: '',
        parentNameName: '',
        
        data: function () {
            return _.extend({
                idName: this.idName,
                nameName: this.nameName,
                idValue: this.model.get(this.idName),
                nameValue: this.model.get(this.nameName),
                parentNameName: this.parentNameName,
                parentNameValue: this.model.get(this.parentNameName),
                parentIdName: this.parentIdName,
                parentIdValue: this.model.get(this.parentIdName),
                foreignScope: this.foreignScope,
            }, Dep.prototype.data.call(this));
        },
        
        init: function () {
            Dep.prototype.init.call(this);
            
            if ((this.mode == 'detail' || this.mode == 'edit') && this.model.getFieldParam(this.name, 'customTooltip')) {
                this.once('after:render', function () {
                    var $a = $('<a href="javascript:" class="text-muted"><span class="glyphicon glyphicon-info-sign"></span></a>');
                    var $label = this.getLabelElement();
                    $label.append(' ');
                    this.getLabelElement().append($a);
                    $a.popover({
                        placement: 'bottom',
                        container: 'body',
                        html: true,
                        content: this.translate(this.model.getFieldParam(this.name, 'tooltipContentLabel') || this.name, 'tooltips', 'MailChimp').replace(/\n/g, "<br />"),
                        trigger: 'click',
                    }).on('shown.bs.popover', function () {
                        $('body').one('click', function () {
                            $a.popover('hide');
                        });
                    });
                }, this);
            }
            
        },
        
        setup: function () {
            this.nameName = this.name + 'Name';
            this.idName = this.name + 'Id';
            
            this.parentIdName = this.name + 'ingId';
            this.parentNameName = this.name + 'ingName';
            
            this.listField = this.options.listField || this.options.defs.params['listField'];
            this.listFieldId = this.listField + 'Id';
            this.foreignScope = this.options.foreignScope || this.foreignScope;

            this.foreignScope = this.foreignScope || this.model.getFieldParam(this.name, 'entity') || this.model.defs.links[this.name].entity;
            var self = this;

            if (this.mode != 'list') {
                this.addActionHandler('selectLink', function () {
                    this.notify('Loading...');
                    this.createView('dialog', 'Advanced:Modals.MailChimp.SelectRecordTree', {
                            scope: this.foreignScope,
                            filters: this.getSelectFilters()
                        }, function (dialog) {
                        dialog.render();
                        self.notify(false);
                        dialog.once('select', function (model) {
                            if (model.get('parentId') == '' || model.get('parentId') == null) {
                                if (confirm(this.translate('Selected grouping', 'labels', 'MailChimp').replace(/\n/g, "<br />"))) {
                                    self.$el.find('button[data-action="selectLink"]').click();
                                }
                            }else {
                                self.$elementName.val(model.get('name'));
                                self.$elementId.val(model.get('id'));
                                self.$elementParentId.val(model.get('parentId'));
                                self.$elementParentName.val(model.get('parentName'));
                                self.trigger('change');
                            }
                        });
                    });
                });
                this.addActionHandler('clearLink', function () {
                    this.$elementName.val('');
                    this.$elementId.val('');
                    this.$elementParentId.val('');
                    this.$elementParentName.val('');
                    this.trigger('change');
                });
            }
        },

        afterRender: function () {
            if (this.mode == 'edit' || this.mode == 'search') {
                this.$elementId = this.$el.find('input[name="' + this.idName + '"]');
                this.$elementName = this.$el.find('input[name="' + this.nameName + '"]');
                this.$elementParentId = this.$el.find('input[name="' + this.parentIdName + '"]');
                this.$elementParentName = this.$el.find('input[name="' + this.parentNameName + '"]');

                this.$elementName.on('change', function () {
                    if (this.$elementName.val() == '') {
                        this.$elementName.val('');
                        this.$elementId.val('');
                        this.$elementParentId.val('');
                        this.$elementParentName.val('');
                        this.trigger('change');
                    }
                }.bind(this));

                if (this.mode == 'edit') {
                    this.$elementName.on('blur', function (e) {
                        if (this.model.has(this.nameName)) {
                            e.currentTarget.value = this.model.get(this.nameName);
                        }
                    }.bind(this));
                } else if (this.mode == 'search') {
                    this.$elementName.on('blur', function (e) {
                        e.currentTarget.value = '';
                    }.bind(this));
                }

                var $elementName = this.$elementName;

                this.listenTo(this.model, 'change:' + this.listFieldId, function () {
                        this.$elementId.val('');
                        this.$elementName.val('');
                        this.$elementParentId.val('');
                        this.$elementParentName.val('');
                }, this);
            
                $elementName.on('change', function () {
                    if (!this.model.get(this.idName)) {
                        $elementName.val(this.model.get(this.nameName));
                        this.$elementParentId.val(this.model.get(this.parentIdName));
                        this.$elementParentName.val(this.model.get(this.parentNameName));
                    }
                }.bind(this));

            }
        },

        fetch: function () {
            var data = {};
            
            var name = this.$el.find('[name="'+this.nameName+'"]').val() || null;
            var id = this.$el.find('[name="'+this.idName+'"]').val() || null;
            
            var parentName = this.$el.find('[name="'+this.parentNameName+'"]').val() || null;
            var parentId = this.$el.find('[name="'+this.parentIdName+'"]').val() || null;
            /*
            if (parentId == '' || parentId == null) {
                data[this.name + 'Name'] = null;
                data[this.name + 'Id'] = null;
                data[this.name + 'ingName'] = name;
                data[this.name + 'ingId'] = id;
                
            } else {
            */
                data[this.name + 'Name'] = name;
                data[this.name + 'Id'] = id;
                data[this.name + 'ingName'] = parentName;
                data[this.name + 'ingId'] = parentId;
            //}

            return data;
        },
        
       getSelectFilters: function () {
            return {
                'list': {
                    field: 'listId',
                    value: this.model.get(this.listFieldId),
                }
            };   
        },

    });
});

