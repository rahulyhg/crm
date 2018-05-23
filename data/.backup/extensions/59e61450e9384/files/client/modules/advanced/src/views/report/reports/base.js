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

Core.define('Advanced:Views.Report.Reports.Base', 'View', function (Dep) {

    return Dep.extend({

        template: 'advanced:report.reports.base',

        data: function () {
            return {
                hasSendEmail: this.getAcl().checkScope('Email'),
                hasRuntimeFilters: this.hasRuntimeFilters()
            }
        },

        events: {
            'click [data-action="run"]': function () {
                this.run();
                this.afterRun();
            },
            'click [data-action="refresh"]': function () {
                this.run();
            },
            'click [data-action="export"]': function () {
                this.export();
            },
            'click [data-action="sendInEmail"]': function () {
                this.actionSendInEmail();
            },
            'click [data-action="showSubReport"]': function (e) {
                var groupValue = $(e.currentTarget).data('group-value');

                this.getCollectionFactory().create(this.model.get('entityType'), function (collection) {
                    collection.url = 'Report/action/runList?id=' + this.model.id + '&groupValue=' + encodeURIComponent(groupValue);

                    if (this.hasRuntimeFilters()) {
                        collection.where = this.lastFetchedWhere;
                    }

                    collection.maxSize = this.getConfig().get('recordsPerPage') || 20;;

                    this.notify('Please wait...');
                    this.listenToOnce(collection, 'sync', function () {
                        this.createView('subReport', 'Advanced:Report.Modals.SubReport', {
                            model: this.model,
                            result: this.result,
                            groupValue: groupValue,
                            collection: collection
                        }, function (view) {
                            view.notify(false);
                            view.render();
                        });
                    }, this);

                    collection.fetch();

                }, this);


            }
        },

        initReport: function () {
            if (!this.hasRuntimeFilters()) {
                this.once('after:render', function () {
                    this.run();
                }, this);
            }

            this.chartType = this.model.get('chartType');

            if (this.hasRuntimeFilters()) {
                this.createRuntimeFilters();
            }
        },

        afterRun: function () {

        },

        createRuntimeFilters: function () {
            var filtersData = this.getStorage().get('state', this.getFilterStorageKey()) || null;

            this.createView('runtimeFilters', 'Advanced:Report.RuntimeFilters', {
                el: this.options.el + ' .report-runtime-filters-contanier',
                entityType: this.model.get('entityType'),
                filterList: this.model.get('runtimeFilters'),
                filtersData: filtersData
            });

        },

        hasRuntimeFilters: function () {
            if ((this.model.get('runtimeFilters') || []).length) {
                return true;
            }
        },

        getRuntimeFilters: function () {
            if (this.hasRuntimeFilters()) {
                this.lastFetchedWhere = this.getView('runtimeFilters').fetch();
                return this.lastFetchedWhere;
            }
            return null;
        },

        getFilterStorageKey: function () {
            return 'report-filters-' + this.model.id;
        },

        storeRuntimeFilters: function (where) {
            if (this.hasRuntimeFilters()) {
                var filtersData = this.getView('runtimeFilters').fetchRaw();

                this.getStorage().set('state', this.getFilterStorageKey(), filtersData);
            }
        },

        actionSendInEmail: function () {
            this.ajaxPostRequest('Report/action/getEmailAttributes', {
                id: this.model.id,
                where: this.getRuntimeFilters()
            }).then(function (attributes) {

                this.createView('compose', 'views/modals/compose-email', {
                    attributes: attributes,
                    keepAttachmentsOnSelectTemplate: true,
                    signatureDisabled: true
                }, function (view) {
                    view.render();
                }, this);
            }.bind(this));
        }

    });

});

