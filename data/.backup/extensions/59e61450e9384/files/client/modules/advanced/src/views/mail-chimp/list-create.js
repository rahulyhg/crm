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
Core.define('Advanced:Views.MailChimp.ListCreate', 'Views.Modals.Edit', function (Dep) {

    return Dep.extend({


        layoutName: 'detailSmall',

        fields: {
            name: {
                type: "varchar",
                required: true
            },
            company: {
                type: "varchar",
                required: true
            },
            address1: {
                type: "varchar",
                required: true
            },
            address2: {
                type: "varchar"
            },
            city: {
                type: "varchar",
                required: true
            },
            state: {
                type: "varchar",
                required: true
            },
            zip: {
                type: "varchar",
                required: true
            },
            country: {
                type: "enum",
                options: ["AD", "AE", "AF", "AG", "AI", "AL", "AM", "AO", "AQ", "AR", "AS", "AT", "AU", "AW", "AX", "AZ", "BA", "BB", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BL", "BM", "BN", "BO", "BQ", "BR", "BS", "BT", "BV", "BW", "BY", "BZ", "CA", "CC", "CD", "CF", "CG", "CH", "CI", "CK", "CL", "CM", "CN", "CO", "CR", "CU", "CV", "CW", "CX", "CY", "CZ", "DE", "DJ", "DK", "DM", "DO", "DZ", "EC", "EE", "EG", "EH", "ER", "ES", "ET", "FI", "FJ", "FK", "FM", "FO", "FR", "GA", "GB", "GD", "GE", "GF", "GG", "GH", "GI", "GL", "GM", "GN", "GP", "GQ", "GR", "GS", "GT", "GU", "GW", "GY", "HK", "HM", "HN", "HR", "HT", "HU", "ID", "IE", "IL", "IM", "IN", "IO", "IQ", "IR", "IS", "IT", "JE", "JM", "JO", "JP", "KE", "KG", "KH", "KI", "KM", "KN", "KP", "KR", "KW", "KY", "KZ", "LA", "LB", "LC", "LI", "LK", "LR", "LS", "LT", "LU", "LV", "LY", "MA", "MC", "MD", "ME", "MF", "MG", "MH", "MK", "ML", "MM", "MN", "MO", "MP", "MQ", "MR", "MS", "MT", "MU", "MV", "MW", "MX", "MY", "MZ", "NA", "NC", "NE", "NF", "NG", "NI", "NL", "NO", "NP", "NR", "NU", "NZ", "OM", "PA", "PE", "PF", "PG", "PH", "PK", "PL", "PM", "PN", "PR", "PS", "PT", "PW", "PY", "QA", "RE", "RO", "RS", "RU", "RW", "SA", "SB", "SC", "SD", "SE", "SG", "SH", "SI", "SJ", "SK", "SL", "SM", "SN", "SO", "SR", "SS", "ST", "SV", "SX", "SY", "SZ", "TC", "TD", "TF", "TG", "TH", "TJ", "TK", "TL", "TM", "TN", "TO", "TR", "TT", "TV", "TW", "TZ", "UA", "UG", "UM", "US", "UY", "UZ", "VA", "VC", "VE", "VG", "VI", "VN", "VU", "WF", "WS", "YE", "YT", "ZA", "ZM", "ZW"],
                required: true,
                default: "US"
            },
            phone: {
                type: "varchar"
            },
            fromName: {
                type: "varchar",
                required: true
            },
            fromEmail: {
                type: "varchar",
                required: true
            },
            subject: {
                type: "varchar",
                required: true
            },
            reminder: {
                type: "text",
                required: true
            },

        },
        setup: function () {

            var self = this;

            this.buttonList = [];

            this.buttonList.push({
                name: 'save',
                text: this.getLanguage().translate('Save'),
                style: 'primary',
                onClick: function (dialog) {
                    var editView = this.getView('edit');

                    var model = editView.model;
                    editView.once('after:save', function () {
                        this.trigger('after:save', model);
                        dialog.close();
                    }, this);

                    var $buttons = dialog.$el.find('.modal-footer button');
                    $buttons.addClass('disabled');
                    $buttons.attr('disabled','disabled');

                    editView.once('cancel:save', function () {
                        $buttons.removeClass('disabled');
                        $buttons.removeAttr('disabled');
                    }, this);

                    editView.save();

                }.bind(this)
            });


            this.buttonList.push({
                name: 'cancel',
                text: this.getLanguage().translate('Cancel'),
                onClick: function (dialog) {
                    dialog.close();
                }
            });

            this.scope = this.scope || this.options.scope;
            this.id = this.options.id;

            if (!this.id) {
                this.header = this.getLanguage().translate('Create');
            } else {
                this.header = this.getLanguage().translate('Edit');
            }
            this.header += ' ' + this.getLanguage().translate(this.scope, 'scopeNames');

            this.waitForView('edit');

            this.getModelFactory().create(this.scope, function (model) {
                model.defs.fields = this.fields;
                this.model = model;
                if (this.id) {
                    model.id = this.id;
                    model.once('sync', function () {
                        this.createEdit(model);
                    }, this);
                    model.fetch();
                } else {
                    model.populateDefaults();
                    if (this.options.relate) {
                        model.setRelate(this.options.relate);
                    }
                    if (this.options.attributes) {
                        model.set(this.options.attributes);
                    }
                    this.createEdit(model);
                }
            }.bind(this));
        },

        createEdit: function (model, callback) {

            var viewName = this.editViewName || this.getMetadata().get('clientDefs.' + model.name + '.recordViews.editQuick') || 'Record.EditSmall';
            var options = {
                model: model,
                el: this.containerSelector + ' .edit-container',
                type: 'editSmall',
                layoutName: this.layoutName || 'detailSmall',
                columnCount: this.columnCount,
                sideView: false,
                isWide: true,
                columnCount: 2,
                buttonsPosition: false,
                exit: function () {},
            };
            this.createView('edit', viewName, options, callback);
        },

    });
});
