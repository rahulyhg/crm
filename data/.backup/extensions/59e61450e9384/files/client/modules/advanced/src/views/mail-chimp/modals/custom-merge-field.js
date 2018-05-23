Core.define('advanced:views/mail-chimp/modals/custom-merge-field', ['views/modal', 'Model'], function (Dep, Model) {

    return Dep.extend({

        template: 'advanced:mail-chimp.modals.custom-merge-field',
        scopeList: [],
        scopesFieldList: {},
        isNew: false,
        reservedMergeFieldList: [
            'INTERESTS',
            'UNSUB',
            'FORWARD',
            'REWARDs',
            'ARCHIVE',
            'USER_URL',
            'DATE',
            'EMAIL',
            'EMAIL_TYPE',
            'TO',
            'ESPID',
            'ESPNM',
            'LNAME',
            'FNAME',
        ],

        availableFieldTypes:[
            'int',
            'float',
            'currency',
            'currencyConverted',
            'datetimeOptional',
            'bool',
            'array',
            'enum',
            'enumInt',
            'enumFloat',
            'link',
            'datetime',
            'date',
            'text',
            'varchar',
            'url',
            'email',
            'phone'
        ],

        availableMergeFieldTypes:[
            'text',
            'number',
            //'address',
            'phone',
            'date',
            'url',
            'birthday',
            'zip'
        ],

        data: function () {
            return {
                scopeList: this.scopeList,
                scopesData: this.scopesFieldList,
                tag: this.mergeFieldData.mergeFieldTag,
                name: this.mergeFieldData.mergeFieldName,
                type: this.mergeFieldData.mergeFieldType || 'text',
                typeList: this.availableMergeFieldTypes,
                editable: this.isNew
            };
        },

        setup: function () {
            this.mergeFieldData = this.options.mergeFieldData || {};
            this.scopeList = this.options.scopeList || [];
            this.isNew = this.mergeFieldData == {} || this.mergeFieldData.mergeFieldTag == undefined || this.mergeFieldData.mergeFieldTag == '';
            this.buttonList = [
                {
                    name: 'apply',
                    label: 'Apply',
                    style: 'primary',
                    onClick: function (dialog) {
                        if (this.fetch()) {
                            this.trigger('apply', this.mergeFieldData);
                            this.close();
                        }
                    }.bind(this),
                },
                {
                    name: 'cancel',
                    label: 'Cancel',
                    onClick: function (dialog) {
                        this.trigger('cancel');
                        dialog.close();
                    }.bind(this)
                }
            ];

            this.header = this.translate('CustomMergeField Header', 'labels', 'MailChimp');

            this.scopeList.forEach(function (scope) {
                this.scopesFieldList[scope] = this.getFieldList(scope);
            }.bind(this));
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.scopeList.forEach(function (scope) {
                if (this.mergeFieldData && this.mergeFieldData.scopes && this.mergeFieldData.scopes[scope]) {
                    this.$el.find('[name="' + scope + 'Field"]').val(this.mergeFieldData.scopes[scope]);
                }
            }.bind(this));
            return true;
        },

        getFieldList: function (scope) {
            var fieldDefs = this.getMetadata().get('entityDefs.' + scope + '.fields') || {};
            var fieldList = Object.keys(fieldDefs).filter(function(field) {
                var type = fieldDefs[field].type;
                if (fieldDefs[field].disabled) return false;

                if (~this.availableFieldTypes.indexOf(type)) {
                    return true;
                }
                return false;
            }.bind(this)).sort(function (v1, v2) {
                 return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
            }.bind(this));
            return fieldList;
        },

        fetch: function () {
            var tag = this.$el.find('[name="mergeFieldTag"]').val().toUpperCase().trim();
            var name = this.$el.find('[name="mergeFieldName"]').val().trim() || '';
            var type = this.$el.find('[name="mergeFieldType"]').val();

            if (!this.isValidTag(tag)) {
                return false;
            }
            if (name == '') {
                this.showValidationMessage(this.translate('Empty Name', 'messages', 'MailChimp'), 'Name');
                return false;
            }
            this.mergeFieldData.mergeFieldTag = tag;
            this.mergeFieldData.mergeFieldName = name;
            this.mergeFieldData.mergeFieldType = type;
            this.mergeFieldData.scopes = {};
            this.scopeList.forEach(function (scope) {
               this.mergeFieldData.scopes[scope] = this.$el.find('[name="' + scope + 'Field"]').val();
            }.bind(this));
            return true;
        },

        isValidTag: function (tag) {
            if (tag.length == 0) {
                this.showValidationMessage(this.translate('The Tag could not be empty', 'messages', 'MailChimp'), 'Tag');
                return false;
            }
            if (this.reservedMergeFieldList.indexOf(tag) > -1) {
                this.showValidationMessage(this.translate('This tag is reserved', 'messages', 'MailChimp'), 'Tag');
                return false;
            }
            var matchRes = tag.match("^[^A-Z$]|[^0-9A-Z_$]");
            if (matchRes !== null) {
                this.showValidationMessage(this.translate('Not valid tag', 'messages', 'MailChimp'), 'Tag');
                return false;
            }
            return true;
        },

        showValidationMessage: function (message, inputName) {
            selector = '[name="mergeField'+inputName+'"]';

            var $el = this.$el.find(selector);
            if (!$el.size() && this.$element) {
                $el = this.$element;
            }
            $el.popover({
                placement: 'bottom',
                container: 'body',
                content: message,
                trigger: 'manual',
            }).popover('show');

            $el.closest('.field').one('mousedown click', function () {
                $el.popover('destroy');
            });

            this.once('render remove', function () {
                if ($el) {
                    $el.popover('destroy');
                }
            });

            if (this._timeout) {
                clearTimeout(this._timeout);
            }

            this._timeout = setTimeout(function () {
                $el.popover('destroy');
            }, 3000);
        },

  })
});
