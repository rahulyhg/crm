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

Core.define('Advanced:Views.Report.RuntimeFilters', 'View', function (Dep) {

    return Dep.extend({

        template: 'advanced:report.runtime-filters',

        setup: function () {
            this.wait(true);

            this.filterList = this.options.filterList;

            this.filtersData = this.options.filtersData || {};

            this.getModelFactory().create(this.options.entityType, function (model) {
                this.model = model;
                this.getCollectionFactory().create(this.options.entityType, function (collection) {

                    Core.require('SearchManager', function (SearchManager) {
                        this.searchManager = new SearchManager(collection, 'report', null, this.getDateTime());
                        this.wait(false);
                    }.bind(this));

                }, this);

            }, this);
        },

        afterRender: function () {
            this.options.filterList.forEach(function (name) {
                var params = this.filtersData[name] || null;
                this.createFilter(name, params);
            }, this);
        },

        createFilter: function (name, params, callback) {
            params = params || {};

            this.$el.find('.filters-row').append('<div class="filter filter-' + Core.Utils.toDom(name) + ' col-sm-4 col-md-3" />');

            var scope = this.model.name;
            var field = name;


            if (~name.indexOf('.')) {
                var link = name.split('.')[0];
                field = name.split('.')[1];
                scope = this.getMetadata().get('entityDefs.' + this.model.name + '.links.' + link + '.entity');
            }
            if (!scope || !field) {
                return;
            }

            this.getModelFactory().create(scope, function (model) {
                this.createView('filter-' + name, 'Search.Filter', {
                    name: field,
                    model: model,
                    params: params,
                    el: this.options.el + ' .filter-' + Core.Utils.toDom(name),
                    notRemovable: true,
                }, function (view) {
                    if (typeof callback === 'function') {
                        view.once('after:render', function () {
                            callback();
                        });
                    }
                    view.render();
                });
            }, this);
        },


        fetchRaw: function () {
            var data = {};
            this.filterList.forEach(function (name) {
                var filterData = this.getView('filter-' + name).getView('field').fetchSearch();

                var prepareItem = function (data, name) {
                    var type = data.type;
                    if (type === 'or' || type === 'and') {
                        (data.value || []).forEach(function (item) {
                            prepareItem(item, name);
                        }, this);
                        return;
                    }

                    var attribute = data.attribute || data.field || name;
                    if (~name.indexOf('.') && !~attribute.indexOf('.')) {
                        var link = name.split('.')[0];
                        attribute = link + '.' + attribute;
                    }
                    data.field = attribute;
                    data.attribute = attribute;
                };

                prepareItem(filterData, name);

                data[name] = filterData;
            }, this);
            return data;
        },

        fetch: function () {
            var data = this.fetchRaw();
            this.searchManager.setAdvanced(data);
            return this.searchManager.getWhere();
        },

    });
});
