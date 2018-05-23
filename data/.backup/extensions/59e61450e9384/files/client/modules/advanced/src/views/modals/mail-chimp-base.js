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

Core.define('Advanced:Views.Modals.MailChimpBase', ['Views.Modal', 'Model'], function (Dep, Model) {

    return Dep.extend({

        template: 'advanced:modals.mail-chimp',
        footer: null,
        footerView: '',
        hasFooter: false,

        data: function () {
            return {
                fieldList: this.fieldList,
                fieldData: this.fieldData,
                hasFooter: this.hasFooter,
            };
        },

        fieldList: [],
        fieldData: {},
        foreignEntity: null,
        parentModel: null,
        scope: 'MailChimp',

        setup: function () {
            this.fieldList = [];
            this.fieldData = {};
            this.buttonList = [
                {
                    name: 'save',
                    label: 'Save',
                    style: 'primary',
                    onClick: function (dialog) {
                       this.fieldList.forEach(function (field) {
                            var view = this.getView(field);
                            if (!view.readOnly) {
                                view.fetchToModel();
                            }
                        }, this);

                        this.listenToOnce(this.model, 'sync', function () {
                            this.notify('Saved', 'success');
                            this.trigger('after:save', this.parentModel);
                            dialog.close();
                        }, this);
                        this.notify('Saving...');
                        this.model.set('foreignEntity',this.foreignEntity);
                        this.model.save();

                    }.bind(this)
                } ,
                {
                    name: 'cancel',
                    label: 'Cancel',
                    onClick: function (dialog) {
                        dialog.close();
                    }
                },
                {
                    name: 'syncNow',
                    text: this.getLanguage().translate('Sync Now', 'labels', this.scope),
                    onClick: function (dialog) {

                        var syncButton = dialog.$el.find('button[data-name="syncNow"]');
                        syncButton.text(this.getLanguage().translate('Sync Now', 'labels', this.scope));
                        syncButton.addClass('disabled');
                        syncButton.attr('disabled','disabled');
                        var attrsInitialy = this.attributes;
                        var attrsBefore = this.model.getClonedAttributes();

                        this.fieldList.forEach(function (field) {
                            var view = this.getView(field);
                            if (!view.readOnly) {
                                view.fetchToModel();
                            }
                        }, this);

                        var data = this.model.getClonedAttributes();

                        var attrs = false;

                        for (var attr in data) {
                            if (_.isEqual(attrsInitialy[attr], data[attr])) {
                                continue;
                            }
                            (attrs || (attrs = {}))[attr] = data[attr];
                        }

                        if (!attrs) {
                            this.scheduleSync();
                            dialog.close();
                            return true;
                        }
                        this.listenToOnce(this.model, 'sync', function () {
                            this.notify('Saved', 'success');
                            this.trigger('after:save', this.parentModel);
                            dialog.close();
                            this.scheduleSync();
                        }, this);
                        this.notify('Saving...');
                        this.model.set('foreignEntity',this.foreignEntity);
                        this.model.save();

                    }.bind(this)
                }
            ];

            this.id = this.options.model.id;
            this.parentModel = this.options.model;

            this.model = new Model();
            this.model.id = this.id;
            this.model.name = 'MailChimp';
            this.model.urlRoot = 'MailChimp';

            this.wait(true);

            this.listenToOnce(this.model, 'sync', function () {
                this.childSetup();

                if (this.hasFooter) {
                    this.createView('dialogFooter', this.footerView, {
                        el: '.mc-dialog-footer',
                        model: this.model
                    });
                }
                this.wait(false);
                this.attributes = this.model.getClonedAttributes();
            }, this);

            this.model.fetch();

        },

        childSetup: function () {
        },

        createFieldView: function (type, view, name, readOnly, params) {
            var fieldView = view || this.getFieldManager().getViewName(type);
            this.createView(name, fieldView, {
                model: this.model,
                el: this.options.el + ' .field-' + name,
                defs: {
                    name: name,
                    params: params
                },
                mode: readOnly ? 'detail' : 'edit',
                readOnly: readOnly,
            });
            this.fieldList.push(name);
        },

        afterRender: function () {
            var syncButton = this.$el.find('button[data-name="syncNow"]');
            if (this.model.get('syncIsRunning')) {
                syncButton.addClass('disabled');
                syncButton.attr('disabled','disabled');
            }
            this.listenTo(this.model, 'change', function () {
                var syncButton = this.$el.find('button[data-name="syncNow"]');
                if (this.model.get('syncIsRunning')) {
                    syncButton.removeClass('disabled');
                    syncButton.removeAttr('disabled');
                }
                syncButton.text(this.getLanguage().translate('Save and Sync Now', 'labels', this.scope));
            }, this);
        },

        scheduleSync: function () {
            this.notify(this.getLanguage().translate('Scheduling Synchronization', 'labels', this.scope));
            $.ajax({
                url: this.model.name + '/scheduleSync/' + this.foreignEntity + "/" + this.model.id,
                type: 'GET',
                success: function () {
                    this.notify(this.getLanguage().translate('Synchronization is scheduled', 'labels', this.scope), 'success');
                }.bind(this),
            });
        },

    });
});

