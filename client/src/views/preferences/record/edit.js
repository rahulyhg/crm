

Core.define('views/preferences/record/edit', 'views/record/edit', function (Dep) {

    return Dep.extend({

        sideView: null,

        buttonList: [
            {
                name: 'save',
                label: 'Save',
                style: 'primary',
            },
            {
                name: 'cancel',
                label: 'Cancel',
            },
            {
                name: 'reset',
                label: 'Reset',
                style: 'danger'
            }
        ],

        dependencyDefs: {
            'smtpAuth': {
                map: {
                    true: [
                        {
                            action: 'show',
                            fields: ['smtpUsername', 'smtpPassword']
                        }
                    ]
                },
                default: [
                    {
                        action: 'hide',
                        fields: ['smtpUsername', 'smtpPassword']
                    }
                ]
            },
            'useCustomTabList': {
                map: {
                    true: [
                        {
                            action: 'show',
                            fields: ['tabList']
                        }
                    ]
                },
                default: [
                    {
                        action: 'hide',
                        fields: ['tabList']
                    }
                ]
            }
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.model.id == this.getUser().id) {
                this.on('after:save', function () {
                    var data = this.model.toJSON();
                    delete data['smtpPassword'];
                    this.getPreferences().set(data);
                    this.getPreferences().trigger('update');
                }, this);
            }

            if (!this.getUser().isAdmin() || this.model.get('isPortalUser')) {
                this.hideField('dashboardLayout');
            }


            var hideNotificationPanel = true;
            if (!this.getConfig().get('assignmentEmailNotifications') || this.model.get('isPortalUser')) {
                this.hideField('receiveAssignmentEmailNotifications');
            } else {
                hideNotificationPanel = false;
            }

            if (!this.getConfig().get('mentionEmailNotifications') || this.model.get('isPortalUser')) {
                this.hideField('receiveMentionEmailNotifications');
            } else {
                hideNotificationPanel = false;
            }

            if (!this.getConfig().get('streamEmailNotifications') && !this.model.get('isPortalUser')) {
                this.hideField('receiveStreamEmailNotifications');
            } else if (!this.getConfig().get('portalStreamEmailNotifications') && this.model.get('isPortalUser')) {
                this.hideField('receiveStreamEmailNotifications');
            } else {
                hideNotificationPanel = false;
            }

            if (hideNotificationPanel) {
                this.hidePanel('notifications');
            }

            if (this.getConfig().get('userThemesDisabled')) {
                this.hideField('theme');
            }

            this.listenTo(this.model, 'after:save', function () {
                if (
                    this.model.get('language') !== this.attributes.language
                    ||
                    this.model.get('theme') !== this.attributes.theme

                ) {
                    window.location.reload();
                }
            }, this);

            this.listenTo(this.model, 'change:smtpSecurity', function (model, smtpSecurity, o) {
                if (!o.ui) return;
                if (smtpSecurity == 'SSL') {
                    this.model.set('smtpPort', '465');
                } else if (smtpSecurity == 'TLS') {
                    this.model.set('smtpPort', '587');
                } else {
                    this.model.set('smtpPort', '25');
                }
            }, this);
        },

        actionReset: function () {
            this.confirm(this.translate('resetPreferencesConfirmation', 'messages'), function () {
                $.ajax({
                    url: 'Preferences/' + this.model.id,
                    type: 'DELETE',
                }).done(function (data) {
                    Core.Ui.success(this.translate('resetPreferencesDone', 'messages'));
                    this.model.set(data);
                    this.getPreferences().set(this.model.toJSON());
                    this.getPreferences().trigger('update');
                }.bind(this));
            }, this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
        },

        exit: function (after) {
            if (after == 'cancel') {
                this.getRouter().navigate('#User/view/' + this.model.id, {trigger: true});
            }
        },

    });

});
