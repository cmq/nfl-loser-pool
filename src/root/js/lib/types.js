var types = {};

/**
 * Convert a variable to a Boolean
 * @param mixed x
 */
types.boolean = function(x) {
    var n;
    if (typeof x === 'undefined' || x === null) {
        // return false if x is undefined or null
        return false;
    }
    if (typeof x === 'boolean') {
        // if x is already a Boolean, return it
        return x;
    }
    if (typeof x === 'string') {
        // if x is a string, see if it can be converted to an integer (yes, this allows for .123 to be FALSE, no matter for now)
        n = parseInt(x, 10);
        if (isNaN(n)) {
            // x can't be converted to a number, return true as long as the string isn't empty
            return x === '' ? false : true;
        } else {
            // x could be converted to a number, return true unless it converted to 0
            return n === 0 ? false : true;
        }
    }
    // if we get here, let javascript imply a Boolean
    return x ? true : false;
};
types.bool = types.boolean;     // alias

/**
 * Convert a variable to a String
 * @param mixed x
 */
types.string = function(x) {
    if (typeof x === 'undefined' || x === null) {
        return '';
    }
    return '' + x;
};

/**
 * Convert a variable to an Integer
 * @param mixed x
 */
types.int = function(x) {
    x = parseInt(x, 10);
    if (isNaN(x)) {
        return 0;
    }
    return x;
};
types.integer = types.int;

/**
 * Convert a variable to a Float
 * @param mixed x
 */
types.float = function(x) {
    x = ('' + x).replace('$', '').replace(',', '');
    x = parseFloat(x);
    if (isNaN(x)) {
        x = 0;
    }
    return parseFloat(x.toFixed(2));
};

/**
 * Convert a database-style date to m/d/y format
 * @param mixed x
 */
types.date = function(x) {
    var td, d, dp;
    x = types.string(x);
    dt = x.split(' ');
    d = dt.length > 1 ? dt[0] : x;
    dp = d.split('-');
    if (dp.length === 3) {
        return [dp[1], dp[2], dp[0]].join('/');
    }
    return '';
};
