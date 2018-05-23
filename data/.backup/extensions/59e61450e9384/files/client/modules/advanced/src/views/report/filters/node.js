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

Core.define('advanced:views/report/filters/node', 'view', function (Dep) {

    return Dep.extend({

        template: 'advanced:report/filters/node',

        events: {
            'click > .button-container > [data-action="addOr"]': function () {
                this.addOrGroup();
            },
            'click > .button-container > [data-action="addAnd"]': function () {
                this.addAndGroup();
            },
            'click > .button-container > [data-action="addNot"]': function () {
                this.addNotGroup();
            },
            'click > .button-container > [data-action="addField"]': function () {
                this.addField();
            },
            'click > .button-container > [data-action="addComplexExpression"]': function () {
                this.addComplexExpression();
            }
        },

        data: function () {
            return {
                notDisabled: this.notDisabled,
                complexExpressionDisabled: this.complexExpressionDisabled
            };
        },

        setup: function () {
            this.dataList = Core.Utils.cloneDeep(this.options.dataList);
            this.scope = this.options.scope;

            this.level = this.options.level || 0;

            this.filterData = this.options.filterData || {}

            if (this.level > 1 || this.filterData.type === 'not') {
                this.notDisabled = true;
            }

            var version = this.getConfig().get('version') || '';
            var arr = version.split('.');
            if (version !== 'dev' && arr.length > 2 && parseInt(arr[0]) * 100 + parseInt(arr[1]) < 407) {
                this.notDisabled = true;
                this.complexExpressionDisabled = true;
            }
        },

        afterRender: function () {
            this.$itemList = this.$el.find('> .item-list');

            this.dataList.forEach(function (item) {
                this.createItem(item);
            }, this);
        },

        fetch: function () {
            var newDataList = [];
            this.dataList.forEach(function (item) {
                var view = this.getView(item.id);
                if (!view) return;
                var itemData = view.fetch();
                newDataList.push(itemData);
            }, this);

            return newDataList;
        },

        createItem: function (item, highlight) {
            var type = item.type;

            if (!item.id) return;

            var $item = $('<div>').attr('data-id', item.id);

            this.$itemList.append($item);

            var view = 'advanced:views/report/filters/container';
            if (~['or', 'and', 'not'].indexOf(type)) {
                view = 'advanced:views/report/filters/container-group';
            } else if (type === 'complexExpression') {
                view = 'advanced:views/report/filters/container-complex';
            } else {
                if (!item.name) return;
            }

            this.createView(item.id, view, {
                el: this.getSelector() + ' [data-id="'+item.id+'"]',
                scope: this.scope,
                filterData: item,
                level: this.level + 1
            }, function (view) {
                if (highlight) {
                    this.listenToOnce(view, 'after:render', function () {
                        if (~['or', 'and', 'not'].indexOf(type)) {
                            var $label = view.$el.find('> label > span');
                            $label.addClass('text-danger');
                            setTimeout(function () {
                                $label.removeClass('text-danger');
                            }, 1500);
                        } else {
                            var $form = view.$el.find('.form-group');
                            $form.addClass('has-error');
                            setTimeout(function () {
                                $form.removeClass('has-error');
                            }, 1500);
                        }
                    }, this);
                }

                view.render();

                this.listenToOnce(view, 'remove-item', function () {
                    this.removeItem(item.id);
                }, this);
            }, this);
        },

        removeItem: function (id) {
            this.clearView(id);

            this.$el.find('[data-id="'+id+'"]').remove();

            var index = -1;
            this.dataList.forEach(function (item, i) {
                if (item.id === id) {
                    index = i;
                }
            }, this);

            if (~index) {
                this.dataList.splice(index, 1);
            }
        },

        addOrGroup: function () {
            var item = {
                id: this.generateId(),
                type: 'or',
                params: {
                    type: 'or',
                    value: []
                }
            };
            this.dataList.push(item);
            this.createItem(item, true);
        },

        addAndGroup: function () {
            var item = {
                id: this.generateId(),
                type: 'and',
                params: {
                    type: 'and',
                    value: []
                }
            };
            this.dataList.push(item);
            this.createItem(item, true);
        },

        addNotGroup: function () {
            var item = {
                id: this.generateId(),
                type: 'not',
                params: {
                    type: 'not',
                    value: []
                }
            };
            this.dataList.push(item);
            this.createItem(item, true);
        },

        addComplexExpression: function () {
            var item = {
                id: this.generateId(),
                type: 'complexExpression',
                params: {
                    function: '',
                    attribute: '',
                    operator: 'equals',
                    formula: ''
                }
            };
            this.dataList.push(item);
            this.createItem(item, true);
        },

        addField: function () {
            this.createView('modal', 'advanced:views/report/modals/add-filter-field', {
                scope: this.scope,
                level: this.level
            }, function (view) {
                view.render();


                this.listenToOnce(view, 'add-field', function (name) {
                    var item = {
                        id: this.generateId(),
                        name: name,
                        params: {}
                    };

                    this.dataList.push(item);
                    this.createItem(item, 1);

                    this.clearView('modal');
                }, this);
            }, this);
        },

        generateId: function () {
            return Math.random().toString(16).slice(2);
        }

    });
});
