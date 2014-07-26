<script>
var wins      = <?php echo CJSON::encode($wins);?>,
    users     = {},
    sortProp  = 'winnings',
    sortPropN = 'winnings',
    sortRev   = true;

// initialize the users array
(function() {
    var i, w, win, user;
    for (w in wins) {
        win = wins[w];
        if (parseInt(win.yr, 10) >= CONF.currentYear) continue;
        if (!users.hasOwnProperty(win.userid)) {
            user = {
                id:            win.user.id,
                username:      win.user.username,
                avatar_ext:    win.user.avatar_ext,
                power_ranking: win.user.power_ranking,
                wins:          0,
                firsts:        0,
                seconds:       0,
                winnings:      0,
                years:         {}
            };
            for (i=CONF.earliestYear; i<CONF.currentYear; i++) {
                user.years[i] = {
                    '1': {
                        place:    0,
                        winnings: 0
                    },
                    '2': {
                        place:    0,
                        winnings: 0
                    },
                    '3': {
                        place:    0,
                        winnings: 0
                    }
                };
            }
            users[win.userid] = user;
        }
        users[win.userid].wins     ++;
        users[win.userid].firsts   += (win.place === 1 ? 1 : 0);
        users[win.userid].seconds  += (win.place === 2 ? 1 : 0);
        users[win.userid].winnings += win.winnings;
        
        users[win.userid].years[win.yr][win.pot].place    = win.place;
        users[win.userid].years[win.yr][win.pot].winnings = win.winnings;
    }
})();


function buildSortFunction(year, pot) {
    return function(obj) {
        if (pot > 0) {
            return obj.years[year][pot].winnings;
        } else {
            return obj.years[year][1].winnings + obj.years[year][2].winnings + obj.years[year][3].winnings;
        }
    };
}

function drawHead(label, propN, prop, rev, colspan, rowspan) {
    var $th = $('<th' + (typeof colspan !== 'undefined' ? ' colspan="' + colspan + '"' : '') + (typeof rowspan !== 'undefined' ? ' rowspan="' + rowspan + '"' : '') + '>' + label + '</th>')
        .addClass(sortPropN == propN ? 'active-sort' : '')
        .on('click', function(e) {
            e.preventDefault();
            sortProp  = prop;
            sortPropN = propN;
            sortRev   = typeof rev !== 'undefined' ? !!rev : true;
            draw();
        });
    return $th;
}

function draw() {
    var self = this,
        i,
        $table = $('<table class="table table-condensed table-bordered table-striped" />'),
        $thead = $('<thead/>'),
        $tr1, $tr2,
        $tbody = $('<tbody/>');
    
    $table.append($thead).append($tbody);

    $thead.append($tr1 = $('<tr class="sortable"/>')
        .append(drawHead('Name', 'username', 'username', false, 1, 2))
    ).append($tr2 = $('<tr class="sortable"/>'));

    $tr1.append(drawHead('Total Winnings', 'winnings', 'winnings', true, 1, 2));
    $tr1.append(drawHead('Cashes', 'wins', 'wins', true, 1, 2));
    $tr1.append(drawHead('1st Places', 'firsts', 'firsts', true, 1, 2));
    $tr1.append(drawHead('2nd Places', 'seconds', 'seconds', true, 1, 2));

    for (i=CONF.currentYear-1; i>=CONF.earliestYear; i--) {
        $tr1.append(drawHead(i, 'year'+i+'pot0', buildSortFunction(i, 0), true, 1 + (i > CONF.earliestYear ? 1 : 0) + (i >= CONF.movFirstYear ? 1 : 0)));
        $tr2.append(drawHead('Stay-<br />Alive', 'year'+i+'pot1', buildSortFunction(i, 1)));
        if (i > CONF.earliestYear) {
            $tr2.append(drawHead('Overall Record', 'year'+i+'pot2', buildSortFunction(i, 2)));
        }
        if (i >= CONF.movFirstYear) {
            $tr2.append(drawHead('Biggest Loser', 'year'+i+'pot3', buildSortFunction(i, 3)));
        }
    }

    globals.bySortedValue(users, sortProp, function(key, value) {
        var $tr, y, p;
        $tbody.append($tr = $('<tr />')
            .append($('<td nowrap="nowrap"/>')
                .append(globals.avatarBubble(users[key], true))
            )
            .append('<td class="text-right">' + globals.dollarFormat(users[key].winnings) + '</td>')
            .append('<td class="text-right">' + users[key].wins + '</td>')
            .append('<td class="text-right">' + users[key].firsts + '</td>')
            .append('<td class="text-right">' + users[key].seconds + '</td>')
        );
        for (y=CONF.currentYear-1; y>=CONF.earliestYear; y--) {
            for (p=1; p<=3; p++) {
                if (p === 1 || (p === 2 && y > CONF.earliestYear) || (p === 3 && y >= CONF.movFirstYear)) {
                    if (users[key].years[y][p].place > 0) {
                        $tr.append('<td class="text-center">' + globals.ordinal(users[key].years[y][p].place) + '<br />(' + globals.dollarFormat(users[key].years[y][p].winnings) + ')</td>');
                    } else {
                        $tr.append('<td>&nbsp;</td>');
                    }
                }
            }
        }
    }, self, sortRev);
    
    $('#winners').html($table);
    globals.lightboxAvatars();
}

$(function() {
    draw();
});
</script>



<?php
// KDHTODO remove
/*
foreach ($wins as $win) {
    echo "{$win['yr']}, pot {$win['pot']}, place {$win['place']}, {$win['winnings']} -- " . $win->user->username . '<br />';
}
*/
?>


<div class="container-fluid">
    <h2>Previous Winners</h2>
    <div id="winners" style="overflow:auto;"></div>
</div>