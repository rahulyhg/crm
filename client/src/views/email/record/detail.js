

Core.define('views/email/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        sideView: 'views/email/record/detail-side',

        duplicateAction: false,

        layoutNameConfigure: function () {
            if (!this.model.isNew()) {
                var isRestricted = false;

                if (this.model.get('status') == 'Sent') {
                    isRestricted = true;
                }

                if (this.model.get('status') == 'Archived') {
                    if (this.model.get('createdById') == 'system' || !this.model.get('createdById') || this.model.get('isImported')) {
                        isRestricted = true;
                    }
                }
                if (isRestricted) {
                    this.layoutName += 'Restricted';
                }
                this.isRestricted = isRestricted;
            }
        },

        init: function () {
            Dep.prototype.init.call(this);

            this.layoutNameConfigure();
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.model.has('isRead') && !this.model.get('isRead')) {
                this.model.set('isRead', true);
            }
            this.listenTo(this.model, 'sync', function () {
                if (!this.model.get('isRead')) {
                    this.model.set('isRead', true);
                }
            }, this);

            if (this.model.get('isHtml') && this.model.get('bodyPlain')) {
                this.dropdownItemList.push({
                    'label': 'Show Plain Text',
                    'name': 'showBodyPlain'
                });
            }

            if (this.model.get('isUsers')) {
                this.dropdownItemList.push({
                    'label': 'Mark as Important',
                    'name': 'markAsImportant',
                    'hidden': this.model.get('isImportant')
                });
                this.dropdownItemList.push({
                    'label': 'Mark as Not Important',
                    'name': 'markAsNotImportant',
                    'hidden': !this.model.get('isImportant')
                });
                this.dropdownItemList.push({
                    'label': 'Move to Trash',
                    'name': 'moveToTrash',
                    'hidden': this.model.get('inTrash')
                });
                this.dropdownItemList.push({
                    'label': 'Retrieve from Trash',
                    'name': 'retrieveFromTrash',
                    'hidden': !this.model.get('inTrash')
                });
                this.dropdownItemList.push({
                    'label': 'Move to Folder',
                    'name': 'moveToFolder'
                });
            }

            this.listenTo(this.model, 'change:isImportant', function () {
                if (this.model.get('isImportant')) {
                    this.hideActionItem('markAsImportant');
                    this.showActionItem('markAsNotImportant');
                } else {
                    this.hideActionItem('markAsNotImportant');
                    this.showActionItem('markAsImportant');
                }
            }, this);

            this.listenTo(this.model, 'change:inTrash', function () {
                if (this.model.get('inTrash')) {
                    this.hideActionItem('moveToTrash');
                    this.showActionItem('retrieveFromTrash');
                } else {
                    this.hideActionItem('retrieveFromTrash');
                    this.showActionItem('moveToTrash');
                }
            }, this);

            this.listenTo(this.model, 'reply', function () {
                this.showField('replies');
                this.model.fetch();
            }, this);
        },

        actionMarkAsImportant: function () {
            $.ajax({
                url: 'Email/action/markAsImportant',
                type: 'POST',
                data: JSON.stringify({
                    id: this.model.id
                })
            });
            this.model.set('isImportant', true);
        },

        actionMarkAsNotImportant: function () {
            $.ajax({
                url: 'Email/action/markAsNotImportant',
                type: 'POST',
                data: JSON.stringify({
                    id: this.model.id
                })
            });
            this.model.set('isImportant', false);
        },

        actionMoveToTrash: function () {
            $.ajax({
                url: 'Email/action/moveToTrash',
                type: 'POST',
                data: JSON.stringify({
                    id: this.model.id
                })
            }).then(function () {
                Core.Ui.warning(this.translate('Moved to Trash', 'labels', 'Email'));
            }.bind(this));
            this.model.set('inTrash', true);
        },

        actionRetrieveFromTrash: function () {
            $.ajax({
                url: 'Email/action/retrieveFromTrash',
                type: 'POST',
                data: JSON.stringify({
                    id: this.model.id
                })
            }).then(function () {
                Core.Ui.warning(this.translate('Retrieved from Trash', 'labels', 'Email'));
            }.bind(this));
            this.model.set('inTrash', false);
        },

        actionMoveToFolder: function () {
            this.createView('dialog', 'views/email-folder/modals/select-folder', {}, function (view) {
                view.render();
                this.listenToOnce(view, 'select', function (folderId, folderName) {
                    this.clearView('dialog');
                    this.ajaxPostRequest('Email/action/moveToFolder', {
                        id: this.model.id,
                        folderId: folderId
                    }).then(function () {
                        if (folderId === 'inbox') {
                            folderId = null;
                        }
                        this.model.set('folderId', folderId);
                        Core.Ui.success(this.translate('Done'));
                    }.bind(this));
                }, this);
            }, this);
        },

        actionShowBodyPlain: function () {
            this.createView('bodyPlain', 'views/email/modals/body-plain', {
                model: this.model
            }, function (view) {
                view.render();
            }.bind(this));
        },

        handleAttachmentField: function () {
            if ((this.model.get('attachmentsIds') || []).length == 0) {
                this.hideField('attachments');
            } else {
                this.showField('attachments');
            }
        },

        handleCcField: function () {
            if (!this.model.get('cc')) {
                this.hideField('cc');
            } else {
                this.showField('cc');
            }
        },

        handleBccField: function () {
            if (!this.model.get('bcc')) {
                this.hideField('bcc');
            } else {
                this.showField('bcc');
            }
        },

        handleRepliesField: function () {
            if ((this.model.get('repliesIds') || []).length == 0) {
                this.hideField('replies');
            } else {
                this.showField('replies');
            }
            if (!this.model.get('repliedId')) {
                this.hideField('replied');
            } else {
                this.showField('replied');
            }
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.model.get('status') === 'Draft') {
                this.setFieldReadOnly('dateSent');
            }

            if (this.isRestricted) {
                this.handleAttachmentField();
                this.listenTo(this.model, 'change:attachmentsIds', function () {
                    this.handleAttachmentField();
                }, this);

                this.handleCcField();
                this.listenTo(this.model, 'change:cc', function () {
                    this.handleCcField();
                }, this);
                this.handleBccField();
                this.listenTo(this.model, 'change:bcc', function () {
                    this.handleBccField();
                }, this);

                this.handleRepliesField();
                this.listenTo(this.model, 'change:repliesIds', function () {
                    this.handleRepliesField();
                }, this);
            }
        },

        send: function () {
            var model = this.model;
            model.set('status', 'Sending');

            var afterSend = function () {
                Core.Ui.success(this.translate('emailSent', 'messages', 'Email'));
                this.trigger('after:send');
            };

            this.once('after:save', afterSend, this);
            this.once('cancel:save', function () {
                this.off('after:save', afterSend);
            }, this);

            this.once('before:save', function () {
                Core.Ui.notify(this.translate('Sending...', 'labels', 'Email'));
            }, this);

            this.save();
        },

        exitAfterDelete: function () {
            var folderId = ((this.collection || {}).data || {}).folderId || null;

            if (folderId === 'inbox') {
                folderId = null;
            }

            var options = {
                isReturn: true,
                isReturnThroughLink: false,
                folder: folderId
            };
            var url = '#' + this.scope;
            var action = null;
            if (folderId) {
                action = 'list';
                url += '/list/folder=' + folderId;
            }
            this.getRouter().dispatch(this.scope, action, options);
            this.getRouter().navigate(url, {trigger: false});
        }

    });
});

