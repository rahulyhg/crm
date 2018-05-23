

Core.define('views/email/record/list', 'views/record/list', function (Dep) {

    return Dep.extend({

        rowActionsView: 'views/email/record/row-actions/default',

        massActionList: ['remove', 'massUpdate'],

        buttonList: [
            {
                name: 'markAllAsRead',
                label: 'Mark all as read',
                style: 'default'
            }
        ],

        setup: function () {
            Dep.prototype.setup.call(this);

            this.massActionList.push('moveToTrash');

            this.massActionList.push('markAsRead');
            this.massActionList.push('markAsNotRead');
            this.massActionList.push('markAsImportant');
            this.massActionList.push('markAsNotImportant');
            this.massActionList.push('moveToFolder');
            this.massActionList.push('retrieveFromTrash');
        },

        massActionMarkAsRead: function () {
            var ids = [];
            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }
            $.ajax({
                url: 'Email/action/markAsRead',
                type: 'POST',
                data: JSON.stringify({
                    ids: ids
                })
            });

            ids.forEach(function (id) {
                var model = this.collection.get(id);
                if (model) {
                    model.set('isRead', true);
                }
            }, this);
        },

        massActionMarkAsNotRead: function () {
            var ids = [];
            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }
            $.ajax({
                url: 'Email/action/markAsNotRead',
                type: 'POST',
                data: JSON.stringify({
                    ids: ids
                })
            });

            ids.forEach(function (id) {
                var model = this.collection.get(id);
                if (model) {
                    model.set('isRead', false);
                }
            }, this);
        },

        massActionMarkAsImportant: function () {
            var ids = [];
            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }
            $.ajax({
                url: 'Email/action/markAsImportant',
                type: 'POST',
                data: JSON.stringify({
                    ids: ids
                })
            });
            ids.forEach(function (id) {
                var model = this.collection.get(id);
                if (model) {
                    model.set('isImportant', true);
                }
            }, this);
        },

        massActionMarkAsNotImportant: function () {
            var ids = [];
            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }
            $.ajax({
                url: 'Email/action/markAsNotImportant',
                type: 'POST',
                data: JSON.stringify({
                    ids: ids
                })
            });
            ids.forEach(function (id) {
                var model = this.collection.get(id);
                if (model) {
                    model.set('isImportant', false);
                }
            }, this);
        },

        massActionMoveToTrash: function () {
            var ids = [];
            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }

            this.ajaxPostRequest('Email/action/moveToTrash', {
                ids: ids
            }).then(function () {
                Core.Ui.success(this.translate('Done'));
            }.bind(this));

            if (this.collection.data.folderId === 'trash') {
                return;
            }

            ids.forEach(function (id) {
                this.collection.trigger('moving-to-trash', id);
                this.removeRecordFromList(id);
            }, this);
        },

        massActionRetrieveFromTrash: function () {
            var ids = [];
            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }

            this.ajaxPostRequest('Email/action/retrieveFromTrash', {
                ids: ids
            }).then(function () {
                Core.Ui.success(this.translate('Done'));
            }.bind(this));

            if (this.collection.data.folderId !== 'trash') {
                return;
            }

            ids.forEach(function (id) {
                this.collection.trigger('retrieving-from-trash', id);
                this.removeRecordFromList(id);
            }, this);
        },

        massActionMoveToFolder: function () {
            var ids = [];
            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }

            this.createView('dialog', 'views/email-folder/modals/select-folder', {}, function (view) {
                view.render();
                this.listenToOnce(view, 'select', function (folderId) {
                    this.clearView('dialog');
                    this.ajaxPostRequest('Email/action/moveToFolder', {
                        ids: ids,
                        folderId: folderId
                    }).then(function () {
                        this.collection.fetch().then(function () {
                            Core.Ui.success(this.translate('Done'));
                        }.bind(this));
                    }.bind(this));
                }, this);
            }, this);
        },

        actionMarkAsImportant: function (data) {
            data = data || {};
            var id = data.id;
            $.ajax({
                url: 'Email/action/markAsImportant',
                type: 'POST',
                data: JSON.stringify({
                    id: id
                })
            });

            var model = this.collection.get(id);
            if (model) {
                model.set('isImportant', true);
            }
        },

        actionMarkAsNotImportant: function (data) {
            data = data || {};
            var id = data.id;
            $.ajax({
                url: 'Email/action/markAsNotImportant',
                type: 'POST',
                data: JSON.stringify({
                    id: id
                })
            });


            var model = this.collection.get(id);
            if (model) {
                model.set('isImportant', false);
            }
        },

        actionMarkAllAsRead: function () {
            $.ajax({
                url: 'Email/action/markAllAsRead',
                type: 'POST'
            });

            this.collection.forEach(function (model) {
                model.set('isRead', true);
            }, this);

            this.collection.trigger('all-marked-read');
        },

        actionMoveToTrash: function (data) {
            var id = data.id;
            this.ajaxPostRequest('Email/action/moveToTrash', {
                id: id
            }).then(function () {
                Core.Ui.warning(this.translate('Moved to Trash', 'labels', 'Email'));
                this.collection.trigger('moving-to-trash', id);
                this.removeRecordFromList(id);
            }.bind(this));
        },

        actionRetrieveFromTrash: function (data) {
            var id = data.id;
            this.ajaxPostRequest('Email/action/retrieveFromTrash', {
                id: id
            }).then(function () {
                Core.Ui.warning(this.translate('Retrieved from Trash', 'labels', 'Email'));
                this.collection.trigger('retrieving-from-trash', id);
                this.removeRecordFromList(id);
            }.bind(this));
        },

        actionMoveToFolder: function (data) {
            var id = data.id;

            this.createView('dialog', 'views/email-folder/modals/select-folder', {}, function (view) {
                view.render();
                this.listenToOnce(view, 'select', function (folderId) {
                    this.clearView('dialog');
                    this.ajaxPostRequest('Email/action/moveToFolder', {
                        id: id,
                        folderId: folderId
                    }).then(function () {
                        this.collection.fetch().then(function () {
                            Core.Ui.success(this.translate('Done'));
                        }.bind(this));
                    }.bind(this));
                }, this);
            }, this);
        }

    });
});
