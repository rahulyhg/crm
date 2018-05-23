/*********************************************************************************
 * The contents of this file are subject to the CoreCRM Advanced
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

Core.define('Advanced:Views.Google.Fields.LabeledArray', 'Views.Fields.Array', function (Dep) {

    return Dep.extend({


        data: function () {
            var itemHtmlList = [];
            (this.selected || []).forEach(function (value) {
                itemHtmlList.push(this.getItemHtml(value));
            }, this);

            return _.extend({
                selected: this.selected,
                translatedOptions: this.translatedOptions,
                hasOptions: this.params.options ? true : false,
                itemHtmlList: itemHtmlList
            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            var t = {};
            var arr = this.params.options;
            for (key in arr) {
                var scope = this.params.options[key]
                 t[scope] = this.translate(scope, 'scopeNamesPlural', 'Global');
            }

            this.listenTo(this.model, 'change:' + this.name, function () {
                this.selected = Core.Utils.clone(this.model.get(this.name));
            }, this);


            this.translatedOptions = null;

            var translatedOptions = {};
            if (this.params.options) {
                this.params.options.forEach(function (o) {
                    if (typeof t === 'object' && o in t) {
                        translatedOptions[o] = t[o];
                    } else {
                        translatedOptions[o] = o;
                    }
                }.bind(this));
                this.translatedOptions = translatedOptions;
            }
            this.selected = Core.Utils.clone(this.model.get(this.name) || []);
            if (Object.prototype.toString.call(this.selected) !== '[object Array]') {
                this.selected = [];
            }
        },

        getItemHtml: function (value) {
            if (this.translatedOptions != null) {
                for (var item in this.translatedOptions) {
                    if (this.translatedOptions[item] == value) {
                        value = item;
                        break;
                    }
                }
            }

            var label = value;
            if (this.translatedOptions) {
                label = ((value in this.translatedOptions) ? this.translatedOptions [value]: value);
            }

            var  identLabel = this.model.get(value + 'IdentificationLabel');
            var identificationLabel = value.substring(0, 1);

            if (identLabel != null) {
                identificationLabel = identLabel;
            }

            var html = '' +
            '<div class="list-group-item link-with-role form-inline" data-value="' + value + '">' +
                '<div class="pull-left" style="width: 92%; display: inline-block;">' +
                    '<input name="translatedValue" data-value="' + value + '" class="role form-control input-sm pull-right" value="'+identificationLabel+'">' +
                    '<div>' + label + '</div>' +
                '</div>' +
                '<div style="width: 8%; display: inline-block; vertical-align: top;">' +
                    '<a href="javascript:" class="pull-right" data-value="' + value + '" data-action="removeValue"><span class="glyphicon glyphicon-remove"></a>' +
                '</div><br style="clear: both;" />' +
            '</div>';

            return html;
        },


        fetch: function () {
            var data = {};
            data[this.name] = Core.Utils.clone(this.selected || []);
            for (key in data[this.name]){
                var scope = data[this.name][key];
                data[scope+ 'IdentificationLabel'] = this.$el.find('.list-group .list-group-item input[name="translatedValue"][data-value="' + scope + '"]').val();
            }
            //hack
            data['calendarEnabled'] = true;
            //end Hack
            return data;
        },

        validateRequired: function () {
            if (this.params.required || this.model.isRequired(this.name)) {
                var value = this.model.get(this.name);
                if (!value || value.length == 0) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg,'.link-container');
                    return true;
                }
                //only one could be without identification label

            }
            //
            var hasEmptyIdentLabel = false;
            for (key in value) {
                var label = this.model.get(value[key] + 'IdentificationLabel');
                if (label == null || label == '') {
                    if (hasEmptyIdentLabel) {
                        var msg = this.translate('fieldLabelIsRequired', 'messages','GoogleCalendar').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                        this.showValidationMessage(msg, '[name="translatedValue"]:last');
                        return true;
                    }
                    hasEmptyIdentLabel = true;

                }
            }

            return false;
        },

    });
});


