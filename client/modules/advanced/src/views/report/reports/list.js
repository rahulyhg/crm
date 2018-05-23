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

Core.define('Advanced:Views.Report.Reports.List', 'Advanced:Views.Report.Reports.Base', function (Dep) {

    return Dep.extend({

        setup: function () {
            this.initReport();
        },

        getListLayout: function () {
            var scope = this.model.get('entityType')
            var layout = [];

            var columnsData = Core.Utils.cloneDeep(this.model.get('columnsData') || {});
            (this.model.get('columns') || []).forEach(function (item) {
                var o = columnsData[item] || {};
                o.name = item;

                if (~item.indexOf('.')) {
                    var a = item.split('.');
                    o.name = item.replace('.', '_');
                    o.notSortable = true;

                    var link = a[0];
                    var field = a[1];

                    var foreignScope = this.getMetadata().get('entityDefs.' + scope + '.links.' + link + '.entity');
                    var label = this.translate(link, 'links', scope) + '.' + this.translate(field, 'fields', foreignScope);

                    o.customLabel = label;

                    var type = this.getMetadata().get('entityDefs.' + foreignScope + '.fields.' + field + '.type');

                    if (type === 'enum') {
                        o.view = 'advanced:views/fields/foreign-enum';
                        o.options = {
                            foreignScope: foreignScope
                        };
                    } else if (type === 'image') {
                        o.view = 'views/fields/image';
                        o.options = {
                            foreignScope: foreignScope
                        };
                    } else if (type === 'file') {
                        o.view = 'views/fields/file';
                        o.options = {
                            foreignScope: foreignScope
                        };
                    } else if (type === 'date') {
                        o.view = 'views/fields/date';
                        o.options = {
                            foreignScope: foreignScope
                        };
                    } else if (type === 'datetime') {
                        o.view = 'views/fields/datetime';
                        o.options = {
                            foreignScope: foreignScope
                        };
                    }
                }
                layout.push(o);
            }, this);
            return layout;
        },

        export: function () {
            var where = this.getRuntimeFilters();

            var url = 'Report/action/exportList';

            var data = {
                id: this.model.id
            };

            var fieldList = [];

            var listLayout = this.getListLayout();
            listLayout.forEach(function (item) {
                fieldList.push(item.name);
            });

            var o = {
                fieldList: fieldList,
                scope: this.model.get('entityType')
            };

            this.createView('dialogExport', 'views/export/modals/export', o, function (view) {
                view.render();
                this.listenToOnce(view, 'proceed', function (dialogData) {
                    if (!dialogData.exportAllFields) {
                        data.attributeList = dialogData.attributeList;
                        data.fieldList = dialogData.fieldList;
                    }
                    data.where = where;
                    data.format = dialogData.format;
                    this.ajaxPostRequest(url, data).then(function (data) {
                        if ('id' in data) {
                            window.location = this.getBasePath() + '?entryPoint=download&id=' + data.id;
                        }
                    }.bind(this));
                }, this);
            }, this);
        },

        run: function () {
            this.notify('Please wait...');

            $container = this.$el.find('.report-results-container');
            $container.empty();

            $listContainer = $('<div>').addClass('report-list');

            $container.append($listContainer);

            this.getCollectionFactory().create(this.model.get('entityType'), function (collection) {
                collection.url = 'Report/action/runList?id=' + this.model.id;
                collection.where = this.getRuntimeFilters();

                var orderByList = this.model.get('orderByList');
                if (orderByList && orderByList !== '') {
                    var arr = orderByList.split(':');
                    collection.sortBy = arr[1];
                    collection.asc = arr[0] === 'ASC';
                }

                collection.maxSize = this.getConfig().get('recordsPerPage') || 20;

                this.listenToOnce(collection, 'sync', function () {
                    this.storeRuntimeFilters();

                    this.createView('list', 'Advanced:Record.ListForReport', {
                        el: this.options.el + ' .report-list',
                        collection: collection,
                        listLayout: this.getListLayout(),
                        displayTotalCount: true,
                        reportId: this.model.id,
                        runtimeWhere: collection.where
                    }, function (view) {
                        this.notify(false);

                        this.listenTo(view, 'after:render', function () {
                            view.$el.find('> .list').addClass('no-side-margin').addClass('no-bottom-margin');
                        }, this);

                        view.render();
                    }, this);
                }, this);

                collection.fetch();

            }, this);

        },

    });

});
