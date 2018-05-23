

Core.define('views/import/detail', 'views/detail', function (Dep) {

    return Dep.extend({

        getHeader: function () {
        	var dt = this.model.get('createdAt');
        	dt = this.getDateTime().toDisplay(dt);
            var name = Handlebars.Utils.escapeExpression(dt);

            return this.buildHeaderHtml([
                '<a href="#' + this.model.name + '/list">' + this.getLanguage().translate(this.model.name, 'scopeNamesPlural') + '</a>',
                name
            ]);
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.setupMenu();

            this.listenTo(this.model, 'change', function () {
                this.setupMenu();
                if (this.isRendered()) {
                    this.getView('header').reRender();
                }
            }, this);
        },

        setupMenu: function () {
            if (this.model.get('importedCount')) {
                var i = 0;
                this.menu.buttons.forEach(function (item) {
                    if (item.action == 'revert') {
                        i = 1;
                    }
                }, this);
                if (!i) {
                    this.menu.buttons.unshift({
                       "label": "Revert Import",
                       "action": "revert",
                       "style": "danger",
                       "acl": "edit"
                    });
                }
            }
            if (this.model.get('duplicateCount')) {
                var i = 0;
                this.menu.buttons.forEach(function (item) {
                    if (item.action == 'removeDuplicates') {
                        i = 1;
                    }
                }, this);
                if (!i) {
                    this.menu.buttons.unshift({
                       "label": "Remove Duplicates",
                       "action": "removeDuplicates",
                       "style": "default",
                       "acl": "edit"
                    });
                }

            }
        },

        actionRevert: function () {
        	this.confirm(this.translate('confirmation', 'messages'), function () {
                $btn = this.$el.find('button[data-action="revert"]');
                $btn.addClass('disabled');
                Core.Ui.notify(this.translate('pleaseWait', 'messages'));

	        	$.ajax({
	        		type: 'POST',
	        		url: 'Import/action/revert',
	        		data: JSON.stringify({
	        			id: this.model.id
	        		})
	        	}).done(function () {
                    Core.Ui.notify(false);

	        		this.getRouter().navigate('#Import/list', {trigger: true});
	        	}.bind(this));
        	}, this);
        },

        actionRemoveDuplicates: function () {
        	this.confirm(this.translate('confirmation', 'messages'), function () {
                $btn = this.$el.find('button[data-action="removeDuplicates"]');
                $btn.addClass('disabled');
                Core.Ui.notify(this.translate('pleaseWait', 'messages'));

	        	$.ajax({
	        		type: 'POST',
	        		url: 'Import/action/removeDuplicates',
	        		data: JSON.stringify({
	        			id: this.model.id
	        		})
	        	}).done(function () {
                    $btn.remove();
                    this.model.fetch();
                    Core.Ui.success(this.translate('duplicatesRemoved', 'messages', 'Import'))
	        	}.bind(this));
        	}, this);
        }

    });
});

