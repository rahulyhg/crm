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

Core.define('Advanced:Views.Google.Panels.GoogleCalendar', 'View', function (Dep) {

    return Dep.extend({

        template: 'advanced:google.panel',
        productName: 'googleCalendar',

        fieldList:[],
        calendarList: [],
        isBlocked: true,

        fields: {
            calendarDirection: {
                type: 'enum',
                options: ["CoreToGC","GCToCore","Both"],
                default: 'Both',
            },
            calendarStartDate: {
                required: true,
                type: 'date'
            },
            calendarEntityTypes: {
                type: 'base',
                view: 'Advanced:Google.Fields.LabeledArray',
                default: ["Call","Meeting"],
                options: ["Call","Meeting"],
                tooltip: true,
                required: true,
            },
            calendarDefaultEntity: {
                type: 'enum',
                options: ["Call","Meeting"],
                default: "Meeting",
                tooltip: true,
            },
            removeGoogleCalendarEventIfRemovedInCore: {
                type: 'bool'
            },
            dontSyncEventAttendees: {
                type: 'bool'
            },
            calendarMainCalendar: {
                type: 'base',
                view: 'Advanced:Google.Fields.MainCalendar',
                required: true,
            },
            calendarMonitoredCalendars: {
                type: 'base',
                view: 'Advanced:Google.Fields.MonitoredCalendars',
            },
        },

        data: function () {
            return {
                integration: this.integration,
                helpText: this.helpText,
                isActive: this.model.get(this.productName+'Enabled') || false,
                isBlocked: this.isBlocked,
                fields: this.fieldList,
                hasFields: this.fieldList.length > 0,
                name: this.productName
            };
        },

        setup: function () {
            this.model = this.options.model;
            this.id = this.options.id;
            this.model.defs.fields = $.extend(this.model.defs.fields, this.fields);
            this.model.populateDefaults();

            this.fieldList = [];

            for(i in this.fields) {
                this.createFieldView(this.fields[i].type, this.fields[i].view || null, i, false);
            }
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

        loadCalendars: function () {
            $.ajax({
                type: 'GET',
                url: 'GoogleCalendar/action/usersCalendars',
                error: function (xhr) {
                    xhr.errorIsHandled = true;
                    if (!this.isBlocked) {
                        this.isBlocked = true;
                        this.model.set(this.productName+'Enabled', false);
                        this._parentView.reRender();
                    }
                }.bind(this),
            }).done(function (calendars) {
                this.model.calendarList = calendars;
                this.checkCalendars();
                if (this.isBlocked) {
                    this.isBlocked = false;
                    this._parentView.reRender();
                }
            }.bind(this));
        },

        checkCalendars: function () {

            var mainCalendar = this.model.get('calendarMainCalendarId');

            if (!(mainCalendar in this.model.calendarList)) {
                this.model.set('calendarMainCalendarId','');
                this.model.set('calendarMainCalendarName','');
                this.getView('calendarMainCalendar').render();
            }

            var monitoredCalendars = this.model.get('calendarMonitoredCalendarsIds') || [];
            var monitoredCalendarsNames = this.model.get('calendarMonitoredCalendarsNames') || [];
            var render = false;

            for (key in monitoredCalendars) {
                if (!(monitoredCalendars[key] in this.model.calendarList)) {
                    delete monitoredCalendarsNames[monitoredCalendars[key]];
                    monitoredCalendars.splice(key, 1);
                    render = true;
                }
            }
            if (monitoredCalendars.length == 0) {
                render = true;
            }
            if (render) {
                this.model.set('calendarMonitoredCalendarsIds', monitoredCalendars);
                this.model.set('calendarMonitoredCalendarsNames',monitoredCalendarsNames);

                this.getView('calendarMonitoredCalendars').render();
            }

        },

        afterRender: function () {

            this.showCalendarFields();

            this.listenTo(this.model, 'change:calendarDirection', function () {
                this.showCalendarFields();
            }, this);

        },

        showCalendarFields: function() {
            var calendarDirection = this.model.get('calendarDirection');

            switch (calendarDirection) {
                case 'CoreToGC':
                    this.hideField('calendarMonitoredCalendars');
                    this.hideField('calendarDefaultEntity');
                    break;
                case 'GCToCore':
                    this.showField('calendarMonitoredCalendars');
                    this.showField('calendarDefaultEntity');
                    break;
                case 'Both':
                    this.showField('calendarMonitoredCalendars');
                    this.showField('calendarDefaultEntity');
                    break;
                default:
                    this.hideField('calendarMonitoredCalendars');
                    this.hideField('calendarDefaultEntity');
            }
        },

        setConnected: function () {
             this.loadCalendars();
        },

        setNotConnected: function () {

        },

        validate: function () {
            this.fieldList.forEach(function (field) {
                var view = this.getView(field);
                if (!view.readOnly && view.$el.is(':visible')) {
                    view.fetchToModel();
                }
            }, this);
            var notValid = false;
            if (this.model.get('enabled') && this.model.get(this.productName+'Enabled')) {
                this.fieldList.forEach(function (field) {
                    notValid = this.getView(field).validate() || notValid;
                }, this);
            }

            var defaultEntity = this.model.get('calendarDefaultEntity');
            var entities = this.model.get('calendarEntityTypes');
            var enititesView = this.getView('calendarEntityTypes');
            var defaultEntityView = this.getView('calendarDefaultEntity');
            if (defaultEntityView.$el.is(':visible')) {
                var defaultIsInList = false;
                var labelDuplicates = false;
                var labels = new Array();
                for (key in entities) {
                    var label = this.model.get(entities[key] + 'IdentificationLabel');
                    if ((label == null || label == '') && defaultEntity != entities[key]) {
                        var msg = this.translate('emptyNotDefaultEnitityLabel', 'messages','GoogleCalendar');
                        enititesView.showValidationMessage(msg, '[name="translatedValue"]:last');
                        notValid |= true;
                    } else {
                        if (labels.indexOf(label) >= 0) {
                            labelDuplicates = true;
                        }
                        labels.push(label);
                    }

                    if (entities[key] == defaultEntity) {
                        defaultIsInList = true;
                    }
                }

                if (!defaultIsInList) {
                    var msg = this.translate('defaultEntityIsRequiredInList', 'messages','GoogleCalendar');
                    defaultEntityView.showValidationMessage(msg);
                    notValid |= true;
                }

                if (labelDuplicates) {
                    var msg = this.translate('notUniqueIdentificationLabel', 'messages','GoogleCalendar');
                    enititesView.showValidationMessage(msg, '[name="translatedValue"]:last');
                    notValid |= true;
                }
            }
            return notValid;
        },

        hideField : function (field) {
             this.$el.find('.cell-' + field).addClass('hidden');
        },

        showField : function (field) {
             this.$el.find('.cell-' + field).removeClass('hidden');
        },
    });

});
