/************************************************************************
 * This file is part of Samex CRM.
 *
 * Samex CRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * Samex CRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Samex CRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Samex CRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/

Core.define('advanced:views/dashlets/report', ['views/dashlets/abstract/base', 'search-manager', 'advanced:report-helper'], function (Dep, SearchManager, ReportHelper) {

    return Dep.extend({

        name: 'Report',

        optionsView: 'advanced:views/dashlets/options/report',

        _template: '<div class="report-results-container" style="height: 100%;"></div>',

        setup: function () {
            this.optionsFields['report'] = {
                type: 'link',
                entity: 'Report',
                required: true,
                view: 'advanced:views/report/fields/dashlet-select'
            };
            this.optionsFields['column'] = {
                'type': 'enum',
                'options': []
            };

            this.reportHelper = new ReportHelper(this.getMetadata(), this.getLanguage(), this.getDateTime());
        },

        getListLayout: function () {
            var scope = this.getOption('entityType')
            var layout = [];

            var columnsData = Core.Utils.cloneDeep(this.columnsData || {});
            (this.columns || []).forEach(function (item) {
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
                    }
                }
                layout.push(o);
            }, this);
            return layout;
        },

        displayError: function (msg) {
            msg = msg || 'error';
            this.$el.find('.report-results-container').html(this.translate(msg, 'errorMessages', 'Report'));
        },

        displayCount: function (count) {
            var $div = $('<div>').css('text-align', 'center')
                                 .css('font-size', '60px')
                                 .addClass('text-primary');

            var $text = $('<span>').html(count.toString());

            $div.append($text);

            var $container = this.$container;
            this.$container.empty();
            this.$container.append($div);
        },

        actionRefresh: function () {
            if (this.hasView('reportChart')) {
                this.clearView('reportChart');
            }
            this.reRender();
        },

        afterRender: function () {
            this.$container = this.$el.find('.report-results-container');
            this.run();
        },

        run: function () {
            var reportId = this.getOption('reportId');
            if (!reportId) {
                this.displayError('selectReport');
                return;
            };

            var entityType = this.getOption('entityType');
            if (!entityType) {
                this.displayError();
                return;
            };

            var type = this.getOption('type');
            if (!type) {
                this.displayError();
                return;
            };

            this.getModelFactory().create('Report', function (model) {
                model.id = reportId;
                this.listenToOnce(model, 'sync', function () {

                    var depth = model.get('depth') || (model.get('groupBy') || []).length;
                    if (type == 'Grid' && !depth) {
                        this.displayError();
                        return;
                    };

                    var columns = this.columns = model.get('columns');
                    this.columnsData = model.get('columnsData') || {};

                    if (type == 'List' && !columns) {
                        this.displayError();
                        return;
                    };

                    var chartType = model.get('chartType');
                    if (type == 'Grid' && !chartType) {
                        this.displayError('noChart');
                        return;
                    };

                    var height;
                    if (type === 'Grid') {
                        var version = this.getConfig().get('version');
                        height = '245px';

                        if (version === 'dev' || parseInt((version || '').charAt(0)) >= 4) {
                            height = '100%';
                            if (depth === 2 || ~['Pie'].indexOf(chartType)) {
                                height = 'calc(100% - 29px)';
                            }
                        }
                    }

                    if (type === 'List') {
                        this.$container.css('height', 'auto');
                    } else {
                        this.$container.css('height', '100%');
                    }

                    this.getCollectionFactory().create(entityType, function (collection) {
                        var searchManager = new SearchManager(collection, 'report', null, this.getDateTime());
                        var where = null;
                        if (this.getOption('filtersData')) {
                            searchManager.setAdvanced(this.getOption('filtersData'));
                            where = searchManager.getWhere();
                        }

                        switch (type) {
                            case 'List':
                                collection.url = 'Report/action/runList?id=' + reportId;
                                collection.where = where;

                                var orderByList = model.get('orderByList');
                                if (orderByList && orderByList !== '') {
                                    var arr = orderByList.split(':');
                                    collection.sortBy = arr[1];
                                    collection.asc = arr[0] === 'ASC';
                                }

                                this.listenToOnce(collection, 'sync', function () {

                                    if (this.getOption('displayOnlyCount')) {
                                        this.displayCount(collection.total);
                                        return;
                                    }

                                    this.createView('list', 'views/record/list', {
                                        el: this.options.el + ' .report-results-container',
                                        collection: collection,
                                        listLayout: this.getListLayout(),
                                        checkboxes: false,
                                        rowActionsView: false,
                                    }, function (view) {
                                        this.notify(false);
                                        view.render();
                                    }.bind(this));
                                }, this);
                                collection.fetch();

                                break;

                            case 'Grid':
                                $.ajax({
                                    url: 'Report/action/run',
                                    data: {
                                        id: reportId,
                                        where: where,
                                    }
                                }).done(function (result) {
                                    var column = this.getOption('column');

                                    this.createView('reportChart', 'Advanced:Report.Reports.Charts.Grid' + depth + chartType, {
                                        el: this.options.el + ' .report-results-container',
                                        column: column,
                                        result: result,
                                        reportHelper: this.reportHelper,
                                        height: height,
                                        colors: result.chartColors || {},
                                        color: result.chartColor || null
                                    }, function (view) {
                                        view.render();
                                    });
                                }.bind(this));

                                break;
                        }

                    }, this);
                }, this);
                model.fetch();
            }, this);
        },

        setupActionList: function () {
            this.actionList.unshift({
                'name': 'viewReport',
                'html': this.translate('View Report', 'labels', 'Report'),
                'url': '#Report/view/' + this.getOption('reportId')
            });
        },

        actionViewReport: function () {
            var reportId = this.getOption('reportId');
            if (reportId) {
                this.getRouter().navigate('#Report/view/' + reportId, {trigger: true});
            }
        },
    });
});


