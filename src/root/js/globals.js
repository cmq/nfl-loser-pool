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

globals.ordinal = function(number) {
    var ends = ['th','st','nd','rd','th','th','th','th','th','th'];
    if ((number % 100) >= 11 && (number % 100) <= 13) {
        return number + 'th';
    }
    return number + ends[number % 10];
}

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

globals.getUserAvatar = function(user, fullsize) {
    var img;
    if (fullsize !== true) {
        img = 't';
    }
    if (user.avatar_ext) {
        img += user.id + '.' + user.avatar_ext;
    } else {
        img += '0.png';
    }
    return CONF.avatarWebDirectory + '/' + img;
};

globals.avatarBubble = function(user, notTiny) {
    return '<div class="profile-bubble"><a href="' + CONF.url.profile(user.id) + '"><img src="' + globals.getUserAvatar(user) + '" class="avatar' + (notTiny !== true ? ' tiny-avatar' : '') + '" />' + user.username + '</a></div>';
};

globals.getTeamLogoOffset = function(team, size) {
    var multiplier = 50;
    var offset     = (team && team.hasOwnProperty('image_offset') ? parseInt(team.image_offset, 10) : 0);
    if (size.toLowerCase == 'large') {
        multiplier = 80;
    }
    return '0 -' + (multiplier * offset) + 'px';
};

globals.getTeamLogoClass = function(pick, currentWeek) {
    var suffix = '';
    suffix += pick.week == currentWeek ? 'medium' : 'small';
    suffix += pick.setbysystem ? '-inactive' : '';
    return suffix;
};

globals.getTeamLogo = function(team, size) {
    return $('<div/>')
        .addClass('logo')
        .addClass('logo-' + size)
        .css('background-position', globals.getTeamLogoOffset(team, size))
        .attr('title', team.longname);
};

globals.shortenYear = function(input) {
    var yr = '' + input;
    if (yr.length === 4) {
        return yr.substr(2, 2);
    }
    return yr;
};

globals.getOldRecord = function(user, currentWeek) {
    var i, wins = losses = 0;
    for (i=0; i<user.picks.length; i++) {
        if (user.picks[i].week < currentWeek) {
            wins   += user.picks[i].incorrect ? 0 : 1;
            losses += user.picks[i].incorrect ? 1 : 0;
        }
    }
    return wins + '-' + losses;
};

globals.getModal = function(id, title, body, footer) {
    // based off the markup examples from twitter bootstrap:  @see http://getbootstrap.com/javascript/#modals
    var $div;
    $div = $('<div class="modal fade" id="' + id + '" tabindex="-1" role="dialog" aria-labelledby="' + id + '-title" aria-hidden="true"/>')
        .append($('<div class="modal-dialog"/>')
            .append($('<div class="modal-content"/>')
                .append($('<div class="modal-header bg-info"/>')
                    .append('<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>')
                    .append($('<h4 class="modal-title" id="' + id + '-title"/>')
                        .append(title)
                    )
                )
                .append($('<div class="modal-body"/>')
                    .append(body)
                )
                .append(typeof footer === 'undefined' ? '' : $('<div class="modal-footer"/>')
                    .append(footer)
                )
            )
        );
    return $div;
};

globals.lightboxAvatars = function() {
    $('img.avatar').unbind('click').on('click', function(e) {
        var $lightbox,
            fullImageUrl = $(this).attr('src').replace(CONF.avatarWebDirectory + '/t', CONF.avatarWebDirectory + '/');
        //$('<div class="avatar-zoom"><img src="' + fullImageUrl + '" /></div>').lightbox_me({centered:true});
        $lightbox = $('<div class="avatar-zoom"><img src="' + fullImageUrl + '" /></div>');
        $lightbox.on('click', function(e) {
            // make it so even clicking on the lightbox itself (not just the lightbox's overlay) closes it
            $lightbox.trigger('close');
        }).lightbox_me({
            centered: true,
            destroyOnClose: true
        });
        e.preventDefault();
        return false;
    });
};

// @see http://stackoverflow.com/questions/5199901/how-to-sort-an-associative-array-by-its-values-in-javascript
globals.bySortedValue = function(obj, propName, callback, context, reverse) {
    var key, length, tuples = [];
    
    for (key in obj) {
        if (propName) {
            if (typeof propName === 'function') {
                tuples.push([key, propName(obj[key], key)]);
            } else if (obj[key].hasOwnProperty(propName)) {
                tuples.push([key, obj[key][propName]]);
            }
        } else {
            tuples.push([key, obj[key]]);
        }
    }
    
    tuples.sort(function(a, b) {
        var val = a[1] < b[1] ? 1 : a[1] > b[1] ? -1 : 0;
        if (reverse === true) {
            val *= -1;
        }
        return val;
    });
    
    if (typeof callback === 'function' && typeof context !== 'undefined') {
        length = tuples.length;
        while (length--) {
            callback.call(context, tuples[length][0], tuples[length][1]);
        }
    }
};