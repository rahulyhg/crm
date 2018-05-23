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

Core.define('Advanced:Views.MailChimp.Fields.MailChimpCampaignLink', 'Advanced:Views.MailChimp.Fields.MailChimpLink', function (Dep) {

    return Dep.extend({

        editTemplate: 'advanced:mail-chimp.fields.campaign-link.edit',
        webIdName: null,
        statusName: null,

        data: function () {
            return _.extend({
                idName: this.idName,
                nameName: this.nameName,
                idValue: this.model.get(this.idName),
                nameValue: this.model.get(this.nameName),
                statusName: this.statusName,
                webIdName: this.webIdName,
                statusValue: this.model.get(this.statusName),
                webIdValue: this.model.get(this.webIdName),
                foreignScope: this.foreignScope,
            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            this.nameName = this.name + 'Name';
            this.idName = this.name + 'Id';
            this.webIdName = this.name + 'WebId';
            this.statusName = this.name + 'Status';

            this.foreignScope = this.options.foreignScope || this.foreignScope;

            this.foreignScope = this.foreignScope || this.model.getFieldParam(this.name, 'entity') || this.model.defs.links[this.name].entity;
            var self = this;

            if (this.mode != 'list') {
                this.addActionHandler('selectLink', function () {
                    this.notify('Loading...');
                    this.createView('dialog', 'Advanced:Modals.MailChimp.SelectRecords', {
                            scope: this.foreignScope,
                            filters: this.getSelectFilters()
                        }, function (dialog) {
                        dialog.render();
                        self.notify(false);
                        dialog.once('select', function (model) {
                            self.$elementName.val(model.get('name'));
                            self.$elementId.val(model.get('id'));
                            self.$elementWebId.val(model.get('webId') || model.get('web_id') || null);
                            self.$elementStatus.val(model.get('status'));
                            self.trigger('change');
                        });
                    });
                });
                this.addActionHandler('clearLink', function () {
                    this.$elementName.val('');
                    this.$elementId.val('');
                    this.$elementWebId.val('');
                    this.$elementStatus.val('');
                    this.trigger('change');
                });
            }
        },

        afterRender: function () {
            if (this.mode == 'edit' || this.mode == 'search') {
                this.$elementId = this.$el.find('input[name="' + this.idName + '"]');
                this.$elementName = this.$el.find('input[name="' + this.nameName + '"]');
                this.$elementWebId = this.$el.find('input[name="' + this.webIdName + '"]');
                this.$elementStatus = this.$el.find('input[name="' + this.statusName + '"]');

                this.$elementName.on('change', function () {
                    if (this.$elementName.val() == '') {
                        this.$elementName.val('');
                        this.$elementId.val('');
                        this.$elementWebId.val('');
                        this.$elementStatus.val('');
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

                this.$elementName.autocomplete({
                    serviceUrl: function (q) {
                        return this.foreignScope + '?offset=0&maxSize=7';
                    }.bind(this),
                    paramName: 'q',
                    minChars: 1,
                    autoSelectFirst: true,
                       formatResult: function (suggestion) {
                        return suggestion.name;
                    },
                    transformResult: function (response) {
                        var response = JSON.parse(response);
                        var list = [];
                        response.list.forEach(function(item) {
                            list.push({
                                id: item.id,
                                name: item.name,
                                data: item.id,
                                value: item.name,
                                webId: item.webId,
                                status: item.status
                            });
                        }, this);
                        return {
                            suggestions: list
                        };
                    }.bind(this),
                    onSelect: function (s) {
                        this.$elementId.val(s.id);
                        this.$elementName.val(s.name);
                        this.$elementWebId.val(s.webId);
                        this.$elementStatus.val(s.status);
                        this.trigger('change');
                    }.bind(this)
                });

                var $elementName = this.$elementName;

                $elementName.on('change', function () {
                    if (!this.model.get(this.idName)) {
                        $elementName.val(this.model.get(this.nameName));
                        this.$elementWebId.val(this.model.get(this.webIdName));
                        this.$elementStatus.val(this.model.get(this.statusName));
                    }
                }.bind(this));

                this.once('render', function () {
                    $elementName.autocomplete('dispose');
                }, this);

                this.once('remove', function () {
                    $elementName.autocomplete('dispose');
                }, this);
            }
        },

        fetch: function () {
            var data = {};
            data[this.nameName] = this.$el.find('[name="'+this.nameName+'"]').val() || null;
            data[this.idName] = this.$el.find('[name="'+this.idName+'"]').val() || null;
            data[this.statusName] = this.$el.find('[name="'+this.statusName+'"]').val() || null;
            data[this.webIdName] = this.$el.find('[name="'+this.webIdName+'"]').val() || null;

            return data;
        },
    });
});

