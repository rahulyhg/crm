

Core.Exceptions = Core.Exceptions || {};

Core.Exceptions.AccessDenied = function (message) {
    this.message = message;
    Error.apply(this, arguments);
}
Core.Exceptions.AccessDenied.prototype = new Error();
Core.Exceptions.AccessDenied.prototype.name = 'AccessDenied';

Core.Exceptions.NotFound = function (message) {
    this.message = message;
    Error.apply(this, arguments);
}
Core.Exceptions.NotFound.prototype = new Error();
Core.Exceptions.NotFound.prototype.name = 'NotFound';


