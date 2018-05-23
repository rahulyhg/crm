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

Core.define('Advanced:Views.Workflow.ActionModals.SendEmail', ['Advanced:Views.Workflow.ActionModals.Base', 'Model'], function (Dep, Model) {

    return Dep.extend({

        template: 'advanced:workflow.action-modals.send-email',

        data: function () {
            return _.extend({
                fromOptions: this.getFromOptions(),
                toOptions: this.getToOptions(),
                fromEmailValue: this.actionData.fromEmail,
                toEmailValue: this.actionData.toEmail,
            }, Dep.prototype.data.call(this));
        },

        events: {
            'change [name="from"]': function (e) {
                this.actionData.from = e.currentTarget.value;
                this.handleFrom();
            },
            'change [name="to"]': function (e) {
            this.actionData.to = e.currentTarget.value;
                this.handleTo();
            },
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.handleFrom();
            this.handleTo();
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.createView('executionTime', 'Advanced:Workflow.ActionFields.ExecutionTime', {
                el: this.options.el + ' .execution-time-container',
                executionData: this.actionData.execution || {},
                entityType: this.entityType
            });

            var model = new Model();

            model.name = 'Workflow';

            model.set({
                emailTemplateId: this.actionData.emailTemplateId,
                emailTemplateName: this.actionData.emailTemplateName,
                doNotStore: this.actionData.doNotStore
            });

            if (this.actionData.toSpecifiedEntityIds) {
                var viewName = 'to' + this.actionData.to.charAt(0).toUpperCase() + this.actionData.to.slice(1);
                model.set(viewName + 'Ids', this.actionData.toSpecifiedEntityIds);
                model.set(viewName + 'Names', this.actionData.toSpecifiedEntityNames);
            }

            this.createView('emailTemplate', 'Fields.Link', {
                el: this.options.el + ' .field-emailTemplate',
                model: model,
                mode: 'edit',
                foreignScope: 'EmailTemplate',
                defs: {
                    name: 'emailTemplate',
                    params: {
                        required: true
                    }
                }
            });

            this.createView('toSpecifiedTeams', 'Fields.LinkMultiple', {
                el: this.options.el + ' .toSpecifiedTeams-container .field-toSpecifiedTeams',
                model: model,
                mode: 'edit',
                foreignScope: 'Team',
                defs: {
                    name: 'toSpecifiedTeams'
                }
            });

            this.createView('toSpecifiedUsers', 'Fields.LinkMultiple', {
                el: this.options.el + ' .toSpecifiedUsers-container .field-toSpecifiedUsers',
                model: model,
                mode: 'edit',
                foreignScope: 'User',
                defs: {
                    name: 'toSpecifiedUsers'
                }
            });

            this.createView('toSpecifiedContacts', 'Fields.LinkMultiple', {
                el: this.options.el + ' .toSpecifiedContacts-container .field-toSpecifiedContacts',
                model: model,
                mode: 'edit',
                foreignScope: 'Contact',
                defs: {
                    name: 'toSpecifiedContacts'
                }
            });

            this.createView('doNotStore', 'Fields.Bool', {
                el: this.options.el + ' .doNotStore-container .field-doNotStore',
                model: model,
                mode: 'edit',
                defs: {
                    name: 'doNotStore'
                }
            });
        },

        handleFrom: function () {
            var value = this.actionData.from;

            if (value == 'specifiedEmailAddress') {
                this.$el.find('.from-email-container').removeClass('hidden');
            } else {
                this.$el.find('.from-email-container').addClass('hidden');
            }
        },

        handleTo: function () {
            var value = this.actionData.to;

            if (value == 'specifiedEmailAddress') {
                this.$el.find('.to-email-container').removeClass('hidden');
            } else {
                this.$el.find('.to-email-container').addClass('hidden');
            }

            var fieldList = ['specifiedTeams', 'specifiedUsers', 'specifiedContacts'];

            fieldList.forEach(function(field) {
                var $elem = this.$el.find('.to' + this.ucfirst(field) + '-container');
                if (!$elem.hasClass('hidden')) {
                    $elem.addClass('hidden');
                }
            }.bind(this));

            if (~fieldList.indexOf(value)) {
                this.$el.find('.to' + this.ucfirst(value) + '-container').removeClass('hidden');
            }
        },

        getFromOptions: function () {
            var html = '';

            var value = this.actionData.from;

            var arr = ['system', 'currentUser', 'specifiedEmailAddress', 'assignedUser'];

            arr.forEach(function (item) {
                var label = this.translate(item, 'emailAddressOptions' , 'Workflow');
                html += '<option value="' + item + '" ' + (item === value ? 'selected' : '') + '>' + label + '</option>';
            }, this);

            return html;
        },

        getToOptions: function () {
            var html = '';

            var value = this.actionData.to;

            var arr = ['currentUser', 'teamUsers', 'specifiedTeams', 'specifiedUsers', 'specifiedContacts', 'specifiedEmailAddress', 'followers', 'followersExcludingAssignedUser'];

            var fieldDefs = this.getMetadata().get('entityDefs.' + this.entityType + '.fields');

            if ('emailAddress' in fieldDefs) {
                var item = 'targetEntity';
                var label = this.translate(item, 'emailAddressOptions' , 'Workflow') + ' (' + this.entityType + ')';
                html += '<option value="' + item + '" ' + (item === value ? 'selected' : '') + '>' + label + '</option>';
            }

            arr.forEach(function (item) {
                var label = this.translate(item, 'emailAddressOptions' , 'Workflow');
                html += '<option value="' + item + '" ' + (item === value ? 'selected' : '') + '>' + label + '</option>';
            }, this);

            var fieldTypeList = ['email'];

            var list = [];

            var linkDefs = this.getMetadata().get('entityDefs.' + this.entityType + '.links');

            Object.keys(linkDefs).forEach(function (link) {
                var list = [];
                if (linkDefs[link].type == 'belongsTo') {
                    var foreignEntityType = linkDefs[link].entity;
                    if (!foreignEntityType) {
                        return;
                    }
                    var fieldDefs = this.getMetadata().get('entityDefs.' + foreignEntityType + '.fields');
                    if ('emailAddress' in fieldDefs && fieldDefs.emailAddress.type === 'email') {
                        var label = this.translate('Related', 'labels', 'Workflow') + ': ' + this.translate(link, 'links' , this.entityType);
                        html += '<option value="' + link + '" ' + (link === value ? 'selected' : '') + '>' + label + '</option>';
                    }
                } else if (linkDefs[link].type == 'belongsToParent') {
                    var label = this.translate('Related', 'labels', 'Workflow') + ': ' + this.translate(link, 'links' , this.entityType);
                    html += '<option value="' + link + '" ' + (link === value ? 'selected' : '') + '>' + label + '</option>';
                }
            }, this);


            return html;
        },

        fetch: function () {
            var emailTemplateView = this.getView('emailTemplate');

            emailTemplateView.fetchToModel();

            if (emailTemplateView.validate()) {
                return;
            }

            var o = emailTemplateView.fetch();

            this.actionData.emailTemplateId = o.emailTemplateId;
            this.actionData.emailTemplateName = o.emailTemplateName;

            this.actionData.from = this.$el.find('[name="from"]').val();
            this.actionData.to = this.$el.find('[name="to"]').val();

            if (~['specifiedTeams', 'specifiedUsers', 'specifiedContacts'].indexOf(this.actionData.to)) {
                //console.log(this.getSpecifiedEntityData(this.actionData.to, 'to'));
                this.actionData = _.extend(this.actionData, this.getSpecifiedEntityData(this.actionData.to, 'to'));
            }

            this.actionData.fromEmail = this.$el.find('[name="fromEmail"]').val();
            this.actionData.toEmail = this.$el.find('[name="toEmail"]').val();
            this.actionData.doNotStore = this.getViewData('doNotStore').doNotStore || false;

            this.actionData.execution = this.actionData.execution || {};

            this.actionData.execution.type = this.$el.find('[name="executionType"]').val();

            if (this.actionData.execution.type != 'immediately') {
                this.actionData.execution.field = this.$el.find('[name="executionField"]').val();
                this.actionData.execution.shiftDays = this.$el.find('[name="shiftDays"]').val();
                this.actionData.execution.shiftUnit = this.$el.find('[name="shiftUnit"]').val();

                if (this.$el.find('[name="shiftDaysOperator"]').val() == 'minus') {
                    this.actionData.execution.shiftDays = (-1) * this.actionData.execution.shiftDays;
                }
            }

            return true;
        },

        getViewData: function (viewName) {
            var view = this.getView(viewName);

            if (view) {
                view.fetchToModel();
                return view.fetch();
            }

            return {};
        },

        getSpecifiedEntityData: function (field, type) {
            var viewName = type + field.charAt(0).toUpperCase() + field.slice(1);
            var view = this.getView(viewName);

            var data = {};

            if (view) {
                view.fetchToModel();
                var viewData = view.fetch();

                data[type + 'SpecifiedEntityName'] = view.foreignScope;
                data[type + 'SpecifiedEntityIds'] = viewData[view.idsName];
                data[type + 'SpecifiedEntityNames'] = viewData[view.nameHashName];
            }

            return data;
        },

        ucfirst: function (string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        },


    });
});
