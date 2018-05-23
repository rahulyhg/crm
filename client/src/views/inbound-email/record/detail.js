

Core.define('views/inbound-email/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            this.setupFieldsBehaviour();
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.initSslFieldListening();

            if (this.wasFetched()) {
                this.setFieldReadOnly('fetchSince');
            }
        },

        wasFetched: function () {
            if (!this.model.isNew()) {
                return !!((this.model.get('fetchData') || {}).lastUID);
            }
            return false;
        },

        controlStatusField: function () {
            var list = ['username', 'port', 'host', 'monitoredFolders'];
            if (this.model.get('status') === 'Active') {
                list.forEach(function (item) {
                    this.setFieldRequired(item);
                }, this);
            } else {
                list.forEach(function (item) {
                    this.setFieldNotRequired(item);
                }, this);
            }
        },

        setupFieldsBehaviour: function () {
            this.controlStatusField();
            this.listenTo(this.model, 'change:status', function (model, value, o) {
                if (o.ui) {
                    this.controlStatusField();
                }
            }, this);

            var handleRequirement = function (model) {
                if (model.get('createCase')) {
                    this.showField('caseDistribution');
                } else {
                    this.hideField('caseDistribution');
                }

                if (model.get('createCase') && ['Round-Robin', 'Least-Busy'].indexOf(model.get('caseDistribution')) != -1) {
                    this.setFieldRequired('team');
                    this.showField('targetUserPosition');
                } else {
                    this.setFieldNotRequired('team');
                    this.hideField('targetUserPosition');
                }
                if (model.get('createCase') && 'Direct-Assignment' === model.get('caseDistribution')) {
                    this.setFieldRequired('assignToUser');
                    this.showField('assignToUser');
                } else {
                    this.setFieldNotRequired('assignToUser');
                    this.hideField('assignToUser');
                }
                if (model.get('createCase') && model.get('createCase') !== '') {
                    this.showField('team');
                } else {
                    this.hideField('team');
                }
            }.bind(this);

            this.listenTo(this.model, 'change:createCase', function (model, value, o) {
                handleRequirement(model);

                if (!o.ui) return;

                if (!model.get('createCase')) {
                    this.model.set({
                        caseDistribution: '',
                        teamId: null,
                        teamName: null,
                        assignToUserId: null,
                        assignToUserName: null,
                        targetUserPosition: ''
                    });
                }
            }, this);

            handleRequirement(this.model);

            this.listenTo(this.model, 'change:caseDistribution', function (model, value, o) {
                handleRequirement(model);

                if (!o.ui) return;

                setTimeout(function () {
                    if (!this.model.get('caseDistribution')) {
                        this.model.set({
                            assignToUserId: null,
                            assignToUserName: null,
                            targetUserPosition: ''
                        });
                    } else if (this.model.get('caseDistribution') === 'Direct-Assignment') {
                        this.model.set({
                            targetUserPosition: ''
                        });
                    } else {
                        this.model.set({
                            assignToUserId: null,
                            assignToUserName: null
                        });
                    }
                }.bind(this), 10);
            });
        },

        initSslFieldListening: function () {
            var sslField = this.getFieldView('ssl');
            this.listenTo(sslField, 'change', function () {
                var ssl = sslField.fetch()['ssl'];
                if (ssl) {
                    this.model.set('port', '993');
                } else {
                    this.model.set('port', '143');
                }
            }, this);
        }

    });

});

