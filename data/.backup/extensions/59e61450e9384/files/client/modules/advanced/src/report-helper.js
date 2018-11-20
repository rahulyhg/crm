/*********************************************************************************
 * The contents of this file are subject to the CRM Advanced
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

Core.define('Advanced:ReportHelper', ['View'], function (Fake) {

    var ReportHelper = function (metadata, language, dateTime) {
        this.metadata = metadata;
        this.language = language;
        this.dateTime = dateTime;
    }

    _.extend(ReportHelper.prototype, {

        formatColumn: function (value, result) {
            if (value in result.columnNameMap) {
                return result.columnNameMap[value];
            }
            return value;
        },

        formatGroup: function (gr, value, result) {
            var entityType = result.entityType;

            if (gr in result.groupNameMap) {
                var value = result.groupNameMap[gr][value] || value;
                if (value === null || value == '') {
                    value = this.language.translate('-Empty-', 'labels', 'Report');
                }
                return value;
            }

            if (~gr.indexOf('MONTH:')) {
                return moment(value + '-01').format('MMM YYYY');
            } else if (~gr.indexOf('DAY:')) {

                var today = moment().tz(this.dateTime.getTimeZone()).startOf('day');
                var dateObj = moment(value);
                var readableFormat = this.dateTime.getReadableDateFormat();

                if (dateObj.format('YYYY') !== today.format('YYYY')) {
                    readableFormat += ', YYYY'
                }

                return dateObj.format(readableFormat);
            }

            if (value === null || value == '') {
                return this.language.translate('-Empty-', 'labels', 'Report');
            }
            return value;
        },

        translateGroupName: function (item, entityType) {
            var hasFunction = false;
            var field = item;
            var scope = entityType;
            var isForeign = false;
            var p = item;
            var link = null
            var func = null;

            if (item == 'COUNT:id') {
                return this.language.translate('COUNT', 'functions', 'Report').toUpperCase();
            }

            if (~item.indexOf(':')) {
                hasFunction = true;
                func = item.split(':')[0];
                p = field = item.split(':')[1];
            }

            if (~p.indexOf('.')) {
                isForeign = true;
                link = p.split('.')[0];
                field = p.split('.')[1];
                scope = this.getMetadata().get('entityDefs.' + entityType + '.links.' + link + '.entity');
            }
            var value = this.language.translate(field, 'fields', scope);
            if (isForeign) {
                value = this.language.translate(link, 'links', entityType) + '.' + value;
            }
            if (hasFunction) {
                value = this.language.translate(func, 'functions', 'Report').toUpperCase() + ': ' + value;
            }



            return value;
        },

        getCode: function () {
            return 'bcac485dee9efd0f36cf6842ad5b69b4';
        }

    });

    return ReportHelper;

});
