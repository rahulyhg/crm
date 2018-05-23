

Core.define('dynamic-logic', [], function () {

    var DynamicLogic = function (defs, recordView) {
        this.defs = defs || {};
        this.recordView = recordView;

        this.fieldTypeList = ['visible', 'required', 'readOnly'];
        this.panelTypeList = ['visible'];

        this.optionsDirtyMap = {};
        this.originalOptions = {};
    }

    _.extend(DynamicLogic.prototype, {

        process: function () {
            var fields = this.defs.fields || {};
            Object.keys(fields).forEach(function (field) {
                var item = (fields[field] || {});
                this.fieldTypeList.forEach(function (type) {
                    if (!(type in item)) return;
                    if (!item[type]) return;
                    var typeItem = (item[type] || {});
                    var conditionGroup = typeItem.conditionGroup;
                    var conditionGroup = (item[type] || {}).conditionGroup;
                    if (!typeItem.conditionGroup) return;
                    var result = this.checkConditionGroup(typeItem.conditionGroup);
                    var methodName;
                    if (result) {
                        methodName = 'makeField' + Core.Utils.upperCaseFirst(type) + 'True';
                    } else {
                        methodName = 'makeField' + Core.Utils.upperCaseFirst(type) + 'False';
                    }
                    this[methodName](field);
                }, this);
            }, this);

            var panels = this.defs.panels || {};
            Object.keys(panels).forEach(function (panel) {
                var item = (panels[panel] || {});
                this.panelTypeList.forEach(function (type) {
                    if (!(type in item)) return;
                    var typeItem = (item[type] || {});
                    var conditionGroup = typeItem.conditionGroup;
                    var conditionGroup = (item[type] || {}).conditionGroup;
                    if (!typeItem.conditionGroup) return;
                    var result = this.checkConditionGroup(typeItem.conditionGroup);
                    var methodName;
                    if (result) {
                        methodName = 'makePanel' + Core.Utils.upperCaseFirst(type) + 'True';
                    } else {
                        methodName = 'makePanel' + Core.Utils.upperCaseFirst(type) + 'False';
                    }
                    this[methodName](panel);
                }, this);
            }, this);

            var options = this.defs.options || {};
            Object.keys(options).forEach(function (field) {
                var itemList = options[field] || [];
                var isMet = false;
                for (var i in itemList) {
                    var item = itemList[i];
                    if (this.checkConditionGroup(item.conditionGroup)) {
                        this.setOptionList(field, item.optionList || []);
                        isMet = true;
                        break;
                    }
                }
                if (!isMet) {
                    this.resetOptionList(field);
                }
            }, this);
        },

        checkConditionGroup: function (data, type) {
            type = type || 'and';

            var list;

            var result = false;
            if (type === 'and') {
                list =  data || [];
                result = true;
                for (var i in list) {
                    if (!this.checkCondition(list[i])) {
                        result = false;
                        break;
                    }
                }
            } else if (type === 'or') {
                list =  data || [];
                for (var i in list) {
                    if (this.checkCondition(list[i])) {
                        result = true;
                        break;
                    }
                }
            } else if (type === 'not') {
                if (data) {
                    result = !this.checkCondition(data);
                }
            }
            return result;
        },

        checkCondition: function (defs) {
            defs = defs || {};
            var type = defs.type || 'equals';

            if (~['or', 'and', 'not'].indexOf(type)) {
                return this.checkConditionGroup(defs.value, type);
            }

            var attribute = defs.attribute;
            var value = defs.value;

            if (!attribute) return;

            var setValue = this.recordView.model.get(attribute);

            if (type === 'equals') {
                if (!value) return;
                return setValue === value;
            } else if (type === 'notEquals') {
                if (!value) return;
                return setValue !== value;
            } else if (type === 'isEmpty') {
                if (Array.isArray(setValue)) {
                    return !setValue.length;
                }
                return setValue === null || (setValue === '') || typeof setValue === 'undefined';
            } else if (type === 'isNotEmpty') {
                if (Array.isArray(setValue)) {
                    return !!setValue.length;
                }
                return setValue !== null && (setValue !== '') && typeof setValue !== 'undefined';
            } else if (type === 'isTrue') {
                return !!setValue;
            } else if (type === 'isFalse') {
                return !setValue;
            } else if (type === 'contains' || type === 'has') {
                if (!setValue) return false;
                return !!~setValue.indexOf(value);
            } else if (type === 'notContains' || type === 'hasNot') {
                if (!setValue) return true;
                return !~setValue.indexOf(value);
            } else if (type === 'greaterThan') {
                return setValue > value;
            } else if (type === 'lessThan') {
                return setValue < value;
            } else if (type === 'greaterThanOrEquals') {
                return setValue >= value;
            } else if (type === 'lessThanOrEquals') {
                return setValue <= value;
            } else if (type === 'in') {
                return ~value.indexOf(setValue);
            } else if (type === 'notIn') {
                return !~value.indexOf(setValue);
            } else if (type === 'isToday') {
                var dateTime = this.recordView.getDateTime();
                if (!setValue) return;
                if (setValue) {
                    if (setValue.length > 10) {
                        return dateTime.toMoment(setValue).isSame(dateTime.getNowMoment(), 'day');
                    } else {
                        return dateTime.toMomentDate(setValue).isSame(dateTime.getNowMoment(), 'day');
                    }
                }
            } else if (type === 'inFuture') {
                var dateTime = this.recordView.getDateTime();
                if (!setValue) return;
                if (setValue) {
                    if (setValue.length > 10) {
                        return dateTime.toMoment(setValue).isAfter(dateTime.getNowMoment(), 'day');
                    } else {
                        return dateTime.toMomentDate(setValue).isAfter(dateTime.getNowMoment(), 'day');
                    }
                }
            } else if (type === 'inPast') {
                var dateTime = this.recordView.getDateTime();
                if (!setValue) return;
                if (setValue) {
                    if (setValue.length > 10) {
                        return dateTime.toMoment(setValue).isBefore(dateTime.getNowMoment(), 'day');
                    } else {
                        return dateTime.toMomentDate(setValue).isBefore(dateTime.getNowMoment(), 'day');
                    }
                }
            }
            return false;
        },

        setOptionList: function (field, optionList) {
            this.recordView.setFieldOptionList(field, optionList);
        },

        resetOptionList: function (field) {
            this.recordView.resetFieldOptionList(field);
        },

        makeFieldVisibleTrue: function (field) {
            this.recordView.showField(field);
        },

        makeFieldVisibleFalse: function (field) {
            this.recordView.hideField(field);
        },

        makeFieldRequiredTrue: function (field) {
            this.recordView.setFieldRequired(field);
        },

        makeFieldRequiredFalse: function (field) {
            this.recordView.setFieldNotRequired(field);
        },

        makeFieldReadOnlyTrue: function (field) {
            this.recordView.setFieldReadOnly(field);
        },

        makeFieldReadOnlyFalse: function (field) {
            this.recordView.setFieldNotReadOnly(field);
        },

        makePanelVisibleTrue: function (field) {
            this.recordView.showPanel(field);
        },

        makePanelVisibleFalse: function (field) {
            this.recordView.hidePanel(field);
        }

    });

    return DynamicLogic;
});

