

Core.define('session-storage', 'storage', function (Dep) {

    return Dep.extend({

        storageObject: sessionStorage,

        get: function (name) {
            var stored = this.storageObject.getItem(name);
            if (stored) {
                var str = stored;
                if (stored[0] == "{" || stored[0] == "[") {
                    try {
                        str = JSON.parse(stored);
                    } catch (error) {
                        str = stored;
                    }
                    stored = str;
                }
                return stored;
            }
            return null;
        },

        set: function (name, value) {
            if (value instanceof Object) {
                value = JSON.stringify(value);
            }
            this.storageObject.setItem(name, value);
        },

        clear: function (name) {
            for (var i in this.storageObject) {
                if (i === name) {
                    delete this.storageObject[i];
                }
            }
        }

    });
});
