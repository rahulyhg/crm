

Core.define('crm:views/knowledge-base-article/list', 'crm:views/document/list', function (Dep) {

    return Dep.extend({

        categoryScope: 'KnowledgeBaseCategory',

        categoryField: 'categories',

        categoryFilterType: 'inCategory',

        getCreateAttributes: function () {
            if (this.currentCategoryId) {
                var names = {};
                names[this.currentCategoryId] = this.currentCategoryName;
                return {
                    categoriesIds: [this.currentCategoryId],
                    categoriesNames: names
                };
            }
        }

    });

});
