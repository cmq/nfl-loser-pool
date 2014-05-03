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

globals.htmlDecode = function(input) {
    return $('<div/>').html(input).text();
};

globals.isEmail = function(e) {
    var regEmail = /^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@([a-zA-Z0-9])+(\.[a-zA-Z0-9_-]+)+$/;
    return (regEmail.test(e));
};

globals.dollarFormat = function(n) {
    n = types.float(n, 0);
    if (isNaN(n)) {
        n = 0;
    }
    return '$' + ('' + Math.abs(n).toFixed(2)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
};

globals.getPotName = function(n) {
    n = types.integer(n, 0);
    switch (n) {
        case 1:
            return 'Stay-Alive';
        case 2:
            return 'Best Record';
        case 3:
            return 'Biggest Loser';
    }
    return '';
};