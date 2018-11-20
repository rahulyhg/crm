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

Core.define('advanced:views/workflow/record/edit-bottom', 'views/record/edit-bottom', function (Dep) {

    return Dep.extend({

        editMode: true,

        template: 'advanced:workflow/record/edit-bottom',

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.model.get('type') === 'scheduled') {
                this.hideConditions();
            }
            this.listenTo(this.model, 'change', function (model, options) {
                if (!options.ui) return;

                if (this.model.hasChanged('entityType') || this.model.hasChanged('type')) {
                    var entityType = model.get('entityType');

                    if (this.model.hasChanged('entityType')) {
                        model.set('conditionsAny', []);
                        model.set('conditionsAll', []);
                        model.set('actions', []);
                    } else {
                        if (this.model.hasChanged('type') && ~['scheduled'].indexOf(this.model.get('type'))) {
                            model.set('conditionsAny', []);
                            model.set('conditionsAll', []);
                        }
                    }

                    if (this.model.get('type') === 'scheduled') {
                        this.hideConditions();
                        if (entityType) {
                            this.showActions();
                        } else {
                            this.hideActions();
                        }
                    } else {
                        if (entityType) {
                            this.showConditions();
                            this.showActions();
                        } else {
                            this.hideConditions();
                            this.hideActions();
                        }
                    }

                }
            }, this);
        },

        afterRender: function () {
            if (!this.model.isNew()) {
                this.showConditions();
                this.showActions();
            } else {
                if (this.model.get('entityType')) {
                    this.showConditions();
                    this.showActions();
                }
            }
        },

        showConditions: function () {
            this.$el.find('.panel-conditions').removeClass('hidden');
            this.clearView('conditions');
            this.createView('conditions', 'advanced:views/workflow/record/conditions', {
                model: this.model,
                el: this.options.el + ' .conditions-container',
                readOnly: !this.editMode
            }, function (view) {
                view.render();
            });
        },

        showActions: function () {
            this.$el.find('.panel-actions').removeClass('hidden');
            this.clearView('actions');
            this.createView('actions', 'advanced:views/workflow/record/actions', {
                model: this.model,
                el: this.options.el + ' .actions-container',
                readOnly: !this.editMode
            }, function (view) {
                view.render();
            });
        },

        hideConditions: function () {
            if (!this.isRendered()) {
                this.once('after:render', function () {
                    this.hideConditions();
                }, this);
                return;
            }
            this.$el.find('.panel-conditions').addClass('hidden');
            this.clearView('conditions');
        },

        hideActions: function () {
            this.$el.find('.panel-actions').addClass('hidden');
            this.clearView('actions');
        },

        getFields: function () {
        },

        getFieldViews: function () {
            return {};
        }

    });
});


