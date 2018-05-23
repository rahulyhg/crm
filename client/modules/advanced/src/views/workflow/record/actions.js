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

Core.define('Advanced:Views.Workflow.Record.Actions', 'View', function (Dep) {

    return Dep.extend({

        template: 'advanced:workflow.record.actions',

        actionTypeList: [
            'sendEmail',
            'triggerWorkflow',
            'createEntity',
            'createRelatedEntity',
            'updateEntity',
            'updateRelatedEntity',
            'createNotification',
            'relateWithEntity',
            'unrelateFromEntity',
            'makeFollowed',
            'runService'
        ],

        events: {
            'click [data-action="addAction"]': function (e) {
                var $target = $(e.currentTarget);
                var actionType = $target.data('type');
                this.addAction(actionType, null, true);
            },
            'click [data-action="removeAction"]': function (e) {
                if (confirm(this.translate('Are you sure?'))) {
                    var $target = $(e.currentTarget);
                    var id = $target.data('id');
                    this.removeAction(id);
                }
            }
        },

        data: function () {
            return {
                actionTypeList: this.actionTypeList,
                entityType: this.entityType,
                readOnly: this.readOnly
            }
        },

        removeAction: function (id)    {
            var $target = this.$el.find('[data-id="' + id + '"]');
            this.clearView('action-' + id);
            $target.parent().remove();
        },

        setup: function () {
            this.readOnly = this.options.readOnly || false;
            this.entityType = this.model.get('entityType');
            this.lastCid = 0;

            this.actionTypeList = Core.Utils.clone(this.actionTypeList);

            if (this.getMetadata().get(['entityDefs', this.entityType, 'fields', 'assignedUser'])) {
                this.actionTypeList.push('applyAssignmentRule');
            }
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
            var actions = Core.Utils.clone(this.model.get('actions') || []);

            actions.forEach(function (data) {
                data = data || {};
                if (!data.type) return;
                this.addAction(data.type, this.cloneData(data));
            }, this);

            if (!this.readOnly) {
                //add sortable
                var $container = this.$el.find('.actions');
                $container.sortable({
                    stop: function () {
                        this.trigger('change');
                    }.bind(this)
                });
            }
        },

        addAction: function (actionType, data, isNew) {
            data = data || {};

            var $container = this.$el.find('.actions');

            var id = data.cid = this.lastCid;
            this.lastCid++;

            var removeLinkHtml = this.readOnly ? '' : '<a href="javascript:" class="pull-right" data-action="removeAction" data-id="'+id+'"><span class="glyphicon glyphicon-remove"></span></a>';

            var html = '<div class="margin clearfix list-group-item">' + removeLinkHtml + '<div class="workflow-action" data-id="' + id + '"></div></div>';

            $container.append($(html));

            if (isNew && !this.readOnly) {
                $container.sortable("refresh");
            }

            this.createView('action-' + id, 'Advanced:Workflow.Actions.' + Core.Utils.upperCaseFirst(actionType), {
                el: this.options.el + ' .workflow-action[data-id="' + id + '"]',
                actionData: data,
                model: this.model,
                entityType: this.entityType,
                actionType: actionType,
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
            var actions = [];

            this.$el.find('.actions .workflow-action').each(function (index, el) {
                var actionId = $(el).attr('data-id');

                if (~actionId) {
                    var view = this.getView('action-' + actionId);
                    if (view) {
                        actions.push(view.fetch());
                    }
                }
            }.bind(this));

            return actions;
        },

    });
});


