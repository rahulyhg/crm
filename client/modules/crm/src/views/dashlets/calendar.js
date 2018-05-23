

Core.define('crm:views/dashlets/calendar', 'views/dashlets/abstract/base', function (Dep) {

    return Dep.extend({

        name: 'Calendar',

        noPadding: true,

        _template: '<div class="calendar-container">{{{calendar}}} </div>',

        init: function () {
            Dep.prototype.init.call(this);
        },

        afterRender: function () {
            var mode = this.getOption('mode');
            if (mode === 'timeline') {
                var userList = [];
                var userIdList = this.getOption('usersIds') || [];
                var userNames = this.getOption('usersNames') || {};
                userIdList.forEach(function (id) {
                    userList.push({
                        id: id,
                        name: userNames[id] || id
                    });
                }, this);

                this.createView('calendar', 'crm:views/calendar/timeline', {
                    el: this.options.el + ' > .calendar-container',
                    header: false,
                    calendarType: 'shared',
                    userList: userList,
                    enabledScopeList: this.getOption('enabledScopeList')
                }, function (view) {
                    view.render();
                }, this);
            } else {
                this.createView('calendar', 'crm:views/calendar/calendar', {
                    mode: mode,
                    el: this.options.el + ' > .calendar-container',
                    header: false,
                    enabledScopeList: this.getOption('enabledScopeList'),
                    containerSelector: this.options.el
                }, function (view) {
                    view.render();
                    this.on('resize', function () {
                        setTimeout(function() {
                            view.adjustSize();
                        }, 50);
                    });
                }, this);
            }
        },

        actionRefresh: function () {
            var view = this.getView('calendar');
            if (!view) return;
            view.actionRefresh();
        },
    });
});


