
Core.define('crm:views/fields/ico', 'views/fields/base', function (Dep) {

    return Dep.extend({

        setup: function () {
            var tpl;

            var icoTpl;
            if (this.params.notRelationship) {
                icoTpl = '<span class="glyphicon glyphicon-{icoName} text-muted action" style="cursor: pointer" title="'+this.translate('View')+'" data-action="quickView" data-id="'+this.model.id+'" data-scope="'+this.model.name+'"></span>';
            } else {
                icoTpl = '<span class="glyphicon glyphicon-{icoName} text-muted action" style="cursor: pointer" title="'+this.translate('View')+'" data-action="quickView" data-id="'+this.model.id+'"></span>';
            }

            switch (this.model.name) {
                case 'Meeting':
                    tpl = icoTpl.replace('{icoName}', 'briefcase');
                    break;
                case 'Call':
                    tpl = icoTpl.replace('{icoName}', 'phone-alt');
                    break;
                case 'Email':
                    tpl = icoTpl.replace('{icoName}', 'envelope');
                    break;
                default:
                    tpl = icoTpl.replace('{icoName}', 'calendar');
                    break;
            }

            this._template = tpl;
        }

    });

});
