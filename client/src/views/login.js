

Core.define('views/login', 'view', function (Dep) {

    return Dep.extend({

        template: 'login',

        views: {
            footer: {
                el: 'body > footer',
                view: 'views/site/footer'
            },
        },

        events: {
            'submit #login-form': function (e) {
                this.login();
                return false;
            },
            'click a[data-action="passwordChangeRequest"]': function (e) {
                this.showPasswordChangeRequest();
            }
        },

        data: function () {
            return {
                logoSrc: this.getLogoSrc()
            };
        },

        getLogoSrc: function () {
            var companyLogoId = this.getConfig().get('companyLogoId');
            if (!companyLogoId) {
                return this.getBasePath() + (this.getThemeManager().getParam('logo') || 'client/img/logo.png');
            }
            return this.getBasePath() + '?entryPoint=LogoImage&id='+companyLogoId+'&t=' + companyLogoId;
        },

        login: function () {
                var userName = $("#field-userName").val();
                var password = $("#field-password").val();

                var $submit = this.$el.find('#btn-login');

                if (userName == '') {
                    var $el = $("#field-userName");

                    var message = this.getLanguage().translate('userCantBeEmpty', 'messages', 'User');
                    $el.popover({
                        placement: 'bottom',
                        content: message,
                        trigger: 'manual',
                    }).popover('show');

                    var $cell = $el.closest('.form-group');
                    $cell.addClass('has-error');
                    this.$el.one('mousedown click', function () {
                        $cell.removeClass('has-error');
                        $el.popover('destroy');
                    });
                    return;
                }

                $submit.addClass('disabled');

                this.notify('Please wait...');

                $.ajax({
                    url: 'App/user',
                    headers: {
                        'Authorization': 'Basic ' + Base64.encode(userName  + ':' + password),
                        'Core-Authorization': Base64.encode(userName + ':' + password)
                    },
                    success: function (data) {
                        this.notify(false);
                        this.trigger('login', {
                            auth: {
                                userName: userName,
                                token: data.token
                            },
                            user: data.user,
                            preferences: data.preferences,
                            acl: data.acl,
                            settings: data.settings
                        });
                    }.bind(this),
                    error: function (xhr) {
                        $submit.removeClass('disabled');
                        if (xhr.status == 401) {
                            this.onWrong();
                        }
                    }.bind(this),
                    login: true,
                });
        },

        onWrong: function () {
            var cell = $('#login .form-group');
            cell.addClass('has-error');
            this.$el.one('mousedown click', function () {
                cell.removeClass('has-error');
            });
            Core.Ui.error(this.translate('wrongUsernamePasword', 'messages', 'User'));
        },

        showPasswordChangeRequest: function () {
            this.notify('Please wait...');
            this.createView('passwordChangeRequest', 'views/modals/password-change-request', {
                url: window.location.href
            }, function (view) {
                view.render();
                view.notify(false);
            });
        }
    });

});
