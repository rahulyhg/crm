

Core.define('crm:views/case/record/panels/activities', 'crm:views/record/panels/activities', function (Dep) {

    return Dep.extend({

        getComposeEmailAttributes: function (scope, data, callback) {
            data = data || {};
            var attributes = {
                status: 'Draft',
                name: '[#' + this.model.get('number') + '] ' + this.model.get('name')
            };

            Core.Ui.notify(this.translate('pleaseWait', 'messages'));

            var parentModel = this.model;

            new Promise(function (resolve, reject) {
                if (parentModel.get('contactsIds') && parentModel.get('contactsIds').length) {
                    this.getCollectionFactory().create('Contact', function (contactList) {
                        var contactListFinal = [];
                        contactList.url = 'Case/' + parentModel.id + '/contacts';
                        contactList.fetch().then(function () {
                            contactList.forEach(function (contact) {
                                if (contact.id == parentModel.get('contactId')) {
                                    contactListFinal.unshift(contact);
                                } else {
                                    contactListFinal.push(contact);
                                }
                            });
                            resolve(contactListFinal);
                        }, function () {resolve([])});
                    }, this);
                } else if (parentModel.get('accountId')) {
                    this.getModelFactory().create('Account', function (account) {
                        account.id = parentModel.get('accountId');
                        account.fetch().then(function () {
                            resolve([account]);
                        }, function () {resolve([])});
                    }, this);
                } else if (parentModel.get('leadId')) {
                    this.getModelFactory().create('Lead', function (account) {
                        lead.id = parentModel.get('leadId');
                        lead.fetch().then(function () {
                            resolve([lead]);
                        }, function () {resolve([])});
                    }, this);
                } else {
                    resolve([]);
                }
            }.bind(this)).then(function (list) {
                attributes.to = '';
                attributes.cc = '';
                attributes.nameHash = {};

                list.forEach(function (model, i) {
                    if (model.get('emailAddress')) {
                        if (i === 0) {
                            attributes.to += model.get('emailAddress') + ';';
                        } else {
                            attributes.cc += model.get('emailAddress') + ';';
                        }
                        attributes.nameHash[model.get('emailAddress')] = model.get('name');
                    }
                });
                Core.Ui.notify(false);

                callback.call(this, attributes);

            }.bind(this));
        }
    });
});

