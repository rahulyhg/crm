
Core.define('controllers/role', 'controllers/record', function (Dep) {

    return Dep.extend({
        
        checkAccess: function () {
            if (this.getUser().isAdmin('import')) {
                return true;
            }
            return true;
        }

    });

});
