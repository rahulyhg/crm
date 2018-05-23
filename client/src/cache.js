

Core.define('cache', [], function () {

    var Cache = function () {
        if (!this.get('app', 'timestamp')) {
            this.storeTimestamp();
        }
    };

    _.extend(Cache.prototype, {

        _prefix: 'cache',

        handleActuality: function (cacheTimestamp) {
            var stored = parseInt(this.get('app', 'cacheTimestamp'));
            if (stored) {
                if (stored !== cacheTimestamp) {
                    this.clear();
                    this.set('app', 'cacheTimestamp', cacheTimestamp);
                    this.storeTimestamp();
                }
            } else {
                this.clear();
                this.set('app', 'cacheTimestamp', cacheTimestamp);
                this.storeTimestamp();
            }
        },

        storeTimestamp: function () {
            var frontendCacheTimestamp = Date.now();
            this.set('app', 'timestamp', frontendCacheTimestamp);
        },

        _composeFullPrefix: function (type) {
            return this._prefix + '-' + type;
        },

        _composeKey: function (type, name) {
            return this._composeFullPrefix(type) + '-' + name;
        },

        _checkType: function (type) {
            if (typeof type === 'undefined' && toString.call(type) != '[object String]') {
                throw new TypeError("Bad type \"" + type + "\" passed to Cache().");
            }
        },

        get: function (type, name) {
            this._checkType(type);

            var key = this._composeKey(type, name);
            var stored = localStorage.getItem(key);
            if (stored) {
                var result = stored;

                if (stored.length > 9 && stored.substr(0, 9) === '__JSON__:') {
                    var jsonString = stored.substr(9);
                    try {
                        result = JSON.parse(jsonString);
                    } catch (error) {
                        result = stored;
                    }
                }
                return result;
            }
            return null;
        },

        set: function (type, name, value) {
            this._checkType(type);
            var key = this._composeKey(type, name);
            if (value instanceof Object || Array.isArray(value)) {
                value = '__JSON__:' + JSON.stringify(value);
            }
            localStorage.setItem(key, value);
        },

        clear: function (type, name) {
            var reText;
            if (typeof type !== 'undefined') {
                if (typeof name === 'undefined') {
                    reText = '^' + this._composeFullPrefix(type);
                } else {
                    reText = '^' + this._composeKey(type, name);
                }
            } else {
                reText = '^' + this._prefix + '-';
            }
            var re = new RegExp(reText);
            for (var i in localStorage) {
                if (re.test(i)) {
                    delete localStorage[i];
                }
            }
        },

    });

    return Cache;

});




