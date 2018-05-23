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

Core.define('advanced:views/dashlets/options/report', ['views/dashlets/options/base', 'advanced:views/report/fields/columns'], function (Dep, Columns) {

    return Dep.extend({

        template: 'advanced:dashlets/options/report',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.reportData = {
                entityType: this.optionsData.entityType || null,
                type: this.optionsData.type || null,
                runtimeFilters: this.optionsData.runtimeFilters || null,
                columns: this.optionsData.columns || null,
            };

            this.listenTo(this.model, 'change:reportName', function (model) {
                setTimeout(function () {
                    model.set('title', model.get('reportName'));
                }, 100);
            }, this);

            this.listenTo(this.model, 'change:reportId', function (model) {
                this.reportData = {};
                this.removeRuntimeFilters();
                this.hideColumnsField();

                this.getView('record').hideField('displayOnlyCount');

                var reportId = model.get('reportId');
                if (!reportId) {
                    return;
                }
                this.getModelFactory().create('Report', function (model) {
                    model.id = reportId;
                    this.listenToOnce(model, 'sync', function () {
                        var reportData = {
                            entityType: model.get('entityType'),
                            type: model.get('type'),
                            runtimeFilters: model.get('runtimeFilters'),
                            columns: model.get('columns')
                        };

                        this.model.set('entityType', model.get('entityType'));

                        this.reportData = reportData;

                        if (this.hasRuntimeFilters()) {
                            this.createRuntimeFilters();
                        }

                        this.handleColumnField();

                    }, this);
                    model.fetch();
                }, this);

            }, this);
        },

        handleColumnField: function () {
            var recordView = this.getView('record');
            if (recordView) {
                recordView.hideField('displayOnlyCount');

                var columnView = recordView.getView('column');
                if (this.reportData.type == 'Grid') {
                    columnView.params.options = this.reportData.columns || [];
                    columnView.translatedOptions = {};
                    Columns.prototype.setupTranslatedOptions.call(columnView);

                    this.$el.find('.cell-column').removeClass('hidden');
                    var recordView = this.getView('record');
                    if ('showField' in recordView) {
                        recordView.showField('column');
                    }

                } else {
                    columnView.params.options = [];
                    this.hideColumnsField();

                    if (this.reportData.type == 'List') {
                        recordView.showField('displayOnlyCount');
                    }
                }
                columnView.render();
            }
        },

        hideColumnsField: function () {
            this.$el.find('.cell-column').addClass('hidden');
            var recordView = this.getView('record');
            if ('hideField' in recordView) {
                recordView.hideField('column');
            }
        },

        afterRender: function () {
            this.handleColumnField();

            if (this.hasRuntimeFilters()) {
                this.createRuntimeFilters();
            }
        },

        hasRuntimeFilters: function () {
            return (this.reportData.runtimeFilters || []).length != 0
        },

        removeRuntimeFilters: function () {
            this.clearView('runtimeFilters');
        },

        createRuntimeFilters: function () {
            this.createView('runtimeFilters', 'Advanced:Report.RuntimeFilters', {
                el: this.options.el + ' .runtime-filters-contanier',
                entityType: this.reportData.entityType,
                filterList: this.reportData.runtimeFilters,
                filtersData: this.optionsData.filtersData || null,
            }, function (view) {
                view.render();
            });
        },

        fetchAttributes: function () {
            var attributes = Dep.prototype.fetchAttributes.call(this);

            if (this.hasRuntimeFilters()) {
                var runtimeFiltersView = this.getView('runtimeFilters');
                if (runtimeFiltersView) {
                    attributes.filtersData = runtimeFiltersView.fetchRaw();
                }
            }
            attributes.entityType = this.reportData.entityType;
            attributes.runtimeFilters = this.reportData.runtimeFilters;
            attributes.type = this.reportData.type;
            attributes.columns = this.reportData.columns;

            return attributes;
        }

    });
});
