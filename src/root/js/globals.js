var globals = {};

globals.isError = function(response) {
    return (response && response.hasOwnProperty('error') && response.error !== '' && response.error !== null);
};

globals.isTruthy = function(v) {
    var n = parseInt(v, 10);
    if (isNaN(n)) {
        return !!v;
    }
    return n > 0;
};

globals.isFalsey = function(v) {
    return !globals.isTruthy;
};

globals.getFromObj = function(obj, names, def) {
    var ret, i;
    
    if (typeof obj === 'object' || $.isArray(obj)) {
        if ($.isArray(names)) {
            for (i=0; i<names.length; i++) {
                ret = $.getObject(names[i], obj);
                if (typeof ret !== 'undefined') {
                    return ret;
                }
            }
        } else {
            ret = $.getObject(names, obj);
        }
    }
    
    if (typeof ret === 'undefined' && typeof def !== 'undefined') {
        ret = def;
    }
    return ret;
};

globals.getWeekName = function(i) {
    i = types.integer(i, 0);
    switch (i) {
        case 21:
            return 'Superbowl';
            break;
        case 20:
            return 'Conf Champ';
            break;
        case 19:
            return 'Divisional';
            break;
        case 18:
            return 'Wild Card';
            break;
        default:
            return '' + i;
            break;
    }
};
