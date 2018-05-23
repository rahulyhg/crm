

Core.define('crm:views/calendar/calendar-page', 'view', function (Dep) {

    return Dep.extend({

        template: 'crm:calendar/calendar-page',

        el: '#main',

        fullCalendarModeList: ['month', 'agendaWeek', 'agendaDay', 'basicWeek', 'basicDay'],

        setup: function () {
            this.mode = this.mode || this.options.mode || null;
            this.date = this.date || this.options.date || null;

            if (!this.mode) {
                this.mode = this.getStorage().get('state', 'calendarMode') || null;
            }

            if (!this.mode || ~this.fullCalendarModeList.indexOf(this.mode)) {
                this.setupCalendar();
            } else {
                if (this.mode === 'timeline') {
                    this.setupTimeline();
                }
            }
        },

        updateUrl: function (trigger) {
            var url = '#Calendar/show/mode=' + this.mode;
            if (this.date) {
                url += '&date=' + this.date;
            }
            if (this.options.userId) {
                url += '&userId=' + this.options.userId;
                if (this.options.userName) {
                    url += '&userName=' + this.options.userName;
                }
            }
            this.getRouter().navigate(url, {trigger: trigger});
        },

        setupCalendar: function () {
            this.createView('calendar', 'crm:views/calendar/calendar', {
                date: this.date,
                userId: this.options.userId,
                userName: this.options.userName,
                mode: this.mode,
                el: '#main > .calendar-container',
            }, function (view) {
                var initial = true;
                this.listenTo(view, 'view', function (date, mode) {
                    this.date = date;
                    this.mode = mode;
                    if (!initial) {
                        this.updateUrl();
                    }
                    initial = false;
                }, this);
                this.listenTo(view, 'change:mode', function (mode) {
                    this.mode = mode;
                    this.getStorage().set('state', 'calendarMode', mode);
                    if (!~this.fullCalendarModeList.indexOf(mode)) {
                        this.updateUrl(true);
                    }
                }, this);
            }, this);
        },

        setupTimeline: function () {
            this.createView('calendar', 'crm:views/calendar/timeline', {
                date: this.date,
                userId: this.options.userId,
                userName: this.options.userName,
                el: '#main > .calendar-container',
            }, function (view) {
                var first = true;
                this.listenTo(view, 'view', function (date, mode) {
                    this.date = date;
                    this.mode = mode;
                    this.updateUrl();
                }, this);
                this.listenTo(view, 'change:mode', function (mode) {
                    this.mode = mode;
                    this.getStorage().set('state', 'calendarMode', mode);
                    this.updateUrl(true);
                }, this);
            }, this);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.translate('Calendar', 'scopeNames'));
        },
    });
});


