/*********************************************************************************
 * The contents of this file are subject to the CRM Advanced
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

Core.define('Advanced:Views.Google.Google', ['Views.ExternalAccount.OAuth2', 'Model'], function (Dep, Model) {

    return Dep.extend({

        template: 'advanced:google.google',

        fields: {
            enabled: {
                type: 'bool'
            }
        },

        isConnected: false,
        activeProducts: [],
        events: {
            'click button[data-action="cancel"]': function () {
                this.getRouter().navigate('#ExternalAccount', {trigger: true});
            },
            'click button[data-action="save"]': function () {
                this.save();
            },
            'click [data-action="connect"]': function () {
                this.connect();
            },
            'click .disconnect-link > a': function () {
                this.disconnect();
                return false;
            },

            'change .enable-panel': function (e) {
                panelName = $(e.currentTarget).attr('name').replace('Enabled','');
                this.togglePanel(panelName);
            }
        },

        data: function () {
            return {
                integration: this.integration,
                helpText: this.helpText,
                isConnected: this.isConnected,
                fields: this.fieldList,
                panels: this.activeProducts
            };
        },

        setup: function () {
            this.integration = this.options.integration;
            this.id = this.options.id;
            this.helpText = false;
            if (this.getLanguage().has(this.integration, 'help', 'ExternalAccount')) {
                this.helpText = this.translate(this.integration, 'help', 'ExternalAccount');
            }

            this.fieldList = [];
            this.dataFieldList = [];
            this.activeProducts = [];
            this.fields =  {
                enabled: {
                    type: 'bool'
                }
            };

            this.model = new Model();
            this.model.id = this.id;
            this.model.name = 'ExternalAccount';
            this.model.urlRoot = 'ExternalAccount';

            this.model.defs = {};

            var products = this.getMetadata().get('integrations.Google.products');
            this.wait(true);
            for (key in products) {
                if (products[key]) {
                    var productScope = key.charAt(0).toUpperCase() + key.slice(1);
                    var isActive = this.getAcl().check(productScope);
                    if (isActive) {
                        this.activeProducts.push(key);
                        var viewName = "Advanced:Google.Panels." + key.charAt(0).toUpperCase() + key.slice(1);
                            this.createView(key, viewName, {
                            el: '.panel-'+key,
                            id: this.id,
                            model: this.model,

                        }, function (view) {
                            this.fieldList.concat(view.fieldList);
                        }.bind(this));
                    }
                }
            }

            for (i in this.activeProducts) {
                this.fields[this.activeProducts[i]+ 'Enabled'] = {type:'bool', default:false};
            }
            this.model.defs.fields = this.fields;
            this.model.populateDefaults();
            for(i in this.fields) {
                this.createFieldView(this.fields[i].type, this.fields[i].view || null, i, false);
            }
            this.listenToOnce(this.model, 'sync', function () {

                $.ajax({
                    url: 'ExternalAccount/action/getOAuth2Info?id=' + this.id,
                    dataType: 'json'
                }).done(function (response) {
                    this.clientId = response.clientId;
                    this.redirectUri = response.redirectUri;
                    if (response.isConnected) {
                        this.setConnected();
                    }
                    this.wait(false);
                }.bind(this));
                //Version Hack
                if (this.model.get('calendarEnabled') && !this.model.get('googleCalendarEnabled')) {
                    this.model.set('googleCalendarEnabled', this.model.get('calendarEnabled'));
                }
                //ENd version hack
            }, this);
            this.model.fetch();
        },

        afterRender: function () {
            if (!this.model.get('enabled')) {
                this.$el.find('.data-panel').addClass('hidden');
            }

            if (this.isConnected) {
                this.$el.find('.data-panel-connected').removeClass('hidden');
            } else {
                this.$el.find('.data-panel-connected').addClass('hidden');
            }

            for (var i in this.activeProducts) {
                if (!this.model.get(this.activeProducts[i]+"Enabled")) {
                    this.hidePanel(this.activeProducts[i]);
                }
            }
            this.listenTo(this.model, 'change:enabled', function () {
                if (this.model.get('enabled')) {
                    this.$el.find('.data-panel').removeClass('hidden');
                } else {
                    this.$el.find('.data-panel').addClass('hidden');
                }
            }, this);
        },

        createFieldView: function (type, view, name, readOnly, params) {
            var fieldView = view || this.getFieldManager().getViewName(type);
            this.createView(name, fieldView, {
                model: this.model,
                el: this.options.el + ' .field-' + name,
                defs: {
                    name: name,
                    params: params
                },
                mode: readOnly ? 'detail' : 'edit',
                readOnly: readOnly,
            });
            this.fieldList.push(name);
        },

        save: function () {
            this.fieldList.forEach(function (field) {
                var view = this.getView(field);
                if (view.el == undefined) {
                    this.model.unset(field);
                } else if (!view.readOnly) {
                    view.fetchToModel();
                }
            }, this);
            var notValid = false;
            if (this.model.get('enabled')) {
                this.fieldList.forEach(function (field) {
                    notValid = this.getView(field).validate() || notValid;
                }, this);

            }
            for (key in this.activeProducts) {
                var product = this.activeProducts[key];
                if (this.model.get(product+'Enabled') || false) {
                    try{
                        notValid |= this.getView(product).validate();
                    } catch(err) {
                        // Handle error(s) here
                    }
                }
            }
            //Version Hack
            if (this.model.get('calendarEnabled')) {
                this.model.unset("calendarEnabled");
            }
            //ENd version hack
            if (notValid) {
                this.notify('Not valid', 'error');
                return;
            }
            this.listenToOnce(this.model, 'sync', function () {
                this.notify('Saved', 'success');
                if (!this.model.get('enabled')) {
                    this.setNotConnected();
                }
            }, this);

            this.model.unset("accessToken");
            this.model.unset("refreshToken");
            this.model.unset("tokenType");

            this.notify('Saving...');
            this.model.save();
        },

        popup: function (options, callback) {
            options.windowName = options.windowName ||  'ConnectWithOAuth';
            options.windowOptions = options.windowOptions || 'location=0,status=0,width=800,height=400';
            options.callback = options.callback || function(){ window.location.reload(); };

            var self = this;

            var path = options.path;

            var arr = [];
            var params = (options.params || {});
            for (var name in params) {
                if (params[name]) {
                    arr.push(name + '=' + encodeURI(params[name]));
                }
            }
            path += '?' + arr.join('&');

            var parseUrl = function (str) {
                var code = null;
                var error = null;

                str = str.substr(str.indexOf('?') + 1, str.length);
                str.split('&').forEach(function (part) {
                    var arr = part.split('=');
                    var name = decodeURI(arr[0]);
                    var value = decodeURI(arr[1] || '');

                    if (name == 'code') {
                        code = value;
                    }
                    if (name == 'error') {
                        error = value;
                    }
                }, this);
                if (code) {
                    return {
                        code: code,
                    }
                } else if (error) {
                    return {
                        error: error,
                    }
                }
            }

            popup = window.open(path, options.windowName, options.windowOptions);
            interval = window.setInterval(function () {
                if (popup.closed) {
                    window.clearInterval(interval);
                } else {
                    var res = parseUrl(popup.location.href.toString());
                    if (res) {
                        callback.call(self, res);
                        popup.close();
                        window.clearInterval(interval);
                    }
                }
            }, 500);
        },

        connect: function () {
            this.notify('Please wait...');
            this.popup({
                path: this.getMetadata().get('integrations.' + this.integration + '.params.endpoint'),
                params: {
                    client_id: this.clientId,
                    redirect_uri: this.redirectUri,
                    scope: this.getMetadata().get('integrations.' + this.integration + '.params.scope'),
                    response_type: 'code',
                    access_type: 'offline',
                    approval_prompt: 'force'
                }
            }, function (res) {
                if (res.error) {
                    this.notify(false);
                    return;
                }
                if (res.code) {
                    this.$el.find('[data-action="connect"]').addClass('disabled');
                    $.ajax({
                        url: 'ExternalAccount/action/authorizationCode',
                        type: 'POST',
                        data: JSON.stringify({
                            'id': this.id,
                            'code': res.code
                        }),
                        dataType: 'json',
                        error: function () {
                            this.$el.find('[data-action="connect"]').removeClass('disabled');
                        }.bind(this)
                    }).done(function (response) {
                        this.notify(false);
                        if (response === true) {
                            this.setConnected();
                        } else {
                            this.setNotConnected();
                        }
                        this.$el.find('[data-action="connect"]').removeClass('disabled');
                    }.bind(this));

                } else {
                    this.notify('Error occured', 'error');
                }
            });
        },

        disconnect: function () {
            if (confirm(this.translate('disconnectConfirmation', 'messages', 'ExternalAccount'))) {
                this.model.set("accessToken", null);
                this.model.set("refreshToken", null);
                this.model.set("tokenType", null);
                this.model.set("enabled", false);

                this.notify('Saving...');
                this.model.save();
                this.setNotConnected();
            }
            this.notify(false);
        },

        setConnected: function () {
            this.isConnected = true;
            this.$el.find('[data-action="connect"]').addClass('hidden');;
            this.$el.find('.connected-label').removeClass('hidden');
            this.$el.find('.data-panel-connected').removeClass('hidden');
            this.$el.find('.disconnect-link').removeClass('hidden');
            var hasAnyPanel = false;

            for (key in this.activeProducts) {
                var product = this.activeProducts[key];
                var view = this.getView(product) || false;
                if (view) {
                    view.setConnected();
                }
                hasAnyPanel |= !view.isBlocked || false;
            }
            if (!hasAnyPanel) {
                this.$el.find('.no-panels').removeClass('hidden');
            } else {
                this.$el.find('.no-panels').addClass('hidden');
            }
        },

        setNotConnected: function () {
            this.isConnected = false;
            this.$el.find('[data-action="connect"]').removeClass('hidden');;
            this.$el.find('.connected-label').addClass('hidden');
            this.$el.find('.data-panel-connected').addClass('hidden');
            this.$el.find('.disconnect-link').addClass('hidden');
            for (key in this.activeProducts) {
                var product = this.activeProducts[key];
                try{
                    this.getView(product).setNotConnected();
                } catch(err) {
                    // Handle error(s) here
                }
            }
        },

        hideField: function (field) {
             this.$el.find('.cell-' + field).addClass('hidden');
        },

        showField: function (field) {
             this.$el.find('.cell-' + field).removeClass('hidden');
        },

        hidePanel: function (panel) {
             this.$el.find('.panel-' + panel + ' .panel-body').addClass('hidden');
        },

        showPanel: function (panel) {
             this.$el.find('.panel-' + panel + ' .panel-body').removeClass('hidden');
        },

        togglePanel: function (panel) {
            if (this.$el.find('.panel-' + panel + ' .panel-body').hasClass('hidden')) {
                this.showPanel(panel);
            } else {
                this.hidePanel(panel);
            }
        },
    });

});
