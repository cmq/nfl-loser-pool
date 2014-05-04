<?php
$currentWeek = getCurrentWeek();
?>

<script>
/****************************************************************/
//Things below here are the real app
/****************************************************************/


// KDHTODO move this elsewhere
// KDHTODO allow view options to be set dynamically now that we're out of angular
function Board(options) {
    var self = this,
        settings = $.extend(true, {
            table:       null,
            order:       'username',
            poll:        false,
            currentUser: CONF.userId,
            currentWeek: CONF.currentWeek,
            currentYear: CONF.currentYear,
            showAdmin:   CONF.isAdmin,
            board:       [],
            viewOptions: {
                // KDHTODO make these togglable in real-time now that angular is gone
                collapseHistory: false,                     // KDHTODO this is defined in userField('collapse_history')
                showBadges:      true,                      // KDHTODO this is defined in userField('show_badges')
                showMov:         true,                      // KDHTODO this is defined in userField('show_mov')
                showLogos:       true                       // KDHTODO this is defined in userField('show_logos')
            }
        }, options),
        polling = false,
        drawn = false,
        lastBoard = JSON.stringify(settings.board),
        $table;


    function getPick(user, week) {
        week--; // provided week is 1-based, make 0-based to access array records
        if (user.picks && user.picks.length > week && user.picks[week].team) {
            return user.picks[week];
        }
        return null;
    }

    function getPickName(user, week, long) {
        var pick = getPick(user, week);
        if (pick && pick.team) {
            if (typeof long !== 'undefined' && long === true) {
                return pick.team.longname;
            }
            return pick.team.shortname;
        }
        return '';
    }
    
    function getPickId(user, week) {
        var pick = getPick(user, week);
        if (pick && pick.team) {
            return pick.team.id;
        }
        return 0;
    }

    function getSortVal(user, key) {
        var week;
        key = key.toLowerCase();
        if (key.indexOf('-') === 0) {
            key = key.substrin(1);
        }
        if (key.indexOf('pick') === 0) {
            week = parseInt(key.substring(4), 10);
            return getPickName(user, week, true).toLowerCase();
        }
        switch (key.toLowerCase()) {
            // KDHTODO add more sorting options, like power points, current rank, etc
            default:
                return user.username.toLowerCase();
        }
    }

    function stylizeMov(mov) {
        mov = parseInt(mov, 10) * -1;
        if (isNaN(mov)) {
            return '';
        }
        if (mov > 0) {
            return '+' + mov;
        }
        return mov;
    }

    // KDHTODO move this function outside of the Board class
    function getUserAvatar(user, fullsize) {
        var img;
        if (fullsize !== true) {
            img = 't';
        }
        if (user.avatar_ext) {
            img += user.id + '.' + user.avatar_ext;
        } else {
            img += '0.png';
        }
        return CONF.avatarWebDirectory + img;
    }

    // KDHTODO move this function outside of the Board class
    function getTeamLogoOffset(team, size) {
        var multiplier = 50;
        var offset     = (team && team.hasOwnProperty('image_offset') ? parseInt(team.image_offset, 10) : 0);
        if (size.toLowerCase == 'large') {
            multiplier = 80;
        }
        return '0 -' + (multiplier * offset) + 'px';
    }
    // KDHTODO move this function outside of the Board class
    function getTeamLogoClass(pick) {
        var suffix = '';
        suffix += pick.week == settings.currentWeek ? 'medium' : 'small';
        suffix += pick.setbysystem ? '-inactive' : '';
        return suffix;
    }
    // KDHTODO move this function outside of the Board class
    function shortenYear(input) {
        var yr = '' + input;
        if (yr.length === 4) {
            return yr.substr(2, 2);
        }
        return yr;
    }
    // KDHTODO move this function outside of the Board class
    function getOldRecord(user) {
        var i, wins = losses = 0;
        for (i=0; i<user.picks.length; i++) {
            if (user.picks[i].week < settings.currentWeek) {    // KDHTODO when we move this function, we'll need to supply currentWeek as a parameter
                wins   += user.picks[i].incorrect ? 0 : 1;
                losses += user.picks[i].incorrect ? 1 : 0;
            }
        }
        return wins + '-' + losses;
    }
    

    this.getTable = function() {
        if (typeof $table == 'function') {
            return $table();
        }
        return $table;
    };

    this.sort = function(newOrder, andRedraw) {
        var i, j, swap, thisVal, compareVal;
        if (typeof newOrder == 'string') {
            settings.order = newOrder;
        }
        for (i=0; i<settings.board.length-1; i++) {
            for (j=i+1; j<settings.board.length; j++) {
                thisVal    = getSortVal(settings.board[i], settings.order);
                compareVal = getSortVal(settings.board[j], settings.order);
                if (compareVal == thisVal && settings.order != 'username') {
                    // secondary sort is always username
                    // KDHTODO alter secondary sort or add tertiary sort for place?
                    thisVal    = getSortVal(settings.board[i], 'username');
                    compareVal = getSortVal(settings.board[j], 'username');
                }
                if ((compareVal < thisVal) === (settings.order.charAt(0) != '-')) {
                    swap              = settings.board[i];
                    settings.board[i] = settings.board[j];
                    settings.board[j] = swap;
                }
            }
        }
        if (andRedraw === true) {
            this.redraw();
        }
    };
    
    this.redraw = function() {
        var $table, $tr, i, j, user, pick, $userDisplay,
            startWeek = settings.viewOptions.collapseHistory ? settings.currentWeek : 1,
            $thead    = $('<thead/>'),
            $tbody    = $('<tbody/>');

        // KDHTODO add support for reversing the sort order (should work by simply prefixing the sort properties with a minus sign)
        // KDHTODO instead of old way, where there tries to be 1 column for who's ahead, let there be a column for every pot, and a final column for total money

        // set up the primary table header
        $tr = $('<tr/>')
            .append($('<th colspan="2"/>')
                .html('User')
                .on('click', function(e) {
                    e.preventDefault();
                    self.sort('username');
                })
            );
        if (settings.viewOptions.collapseHistory) {
            $tr.append($('<th/>')
                .html('Weeks 1 - ' + (settings.currentWeek-1))
                .on('click', function(e) {
                    e.preventDefault();
                    // KDHTODO implement sorting by early-season record
                })
            );
        }
        for (i=startWeek; i<=21; i++) {
            $tr.append($('<th/>')
                .html(globals.getWeekName(i))
                .on('click', function(e) {
                    e.preventDefault();
                    self.sort('pick' + i);
                })
            );
        }
        $thead.append($tr);

        // add the admin-correction-link row to the header
        if (settings.showAdmin) {
            $tr = $('<tr/>')
                .append('<th colspan="2"/>');
            if (settings.viewOptions.collapseHistory) {
                $tr.append('<th/>');
            }
            for (i=startWeek; i<=21; i++) {
                $tr.append($('<th/>')
                    .html('<a href="' + CONF.url.showCorrect + '?week=' + i + '"><span class="glyphicon glyphicon-flash"></span></a>')
                    .on('click', function(e) {
                        e.preventDefault();
                        self.sort('pick' + i);
                    })
                );
            }
        }
        $thead.append($tr);

        // build the table body
        for (i=0; i<settings.board.length; i++) {
            user = settings.board[i];

            // draw the start of the row
            $tr = $('<tr />');
            if (user.id == settings.currentUser) {
                $tr.addClass('success');
            }
            $tr.append('<td>' + (i+1) + '</td>');
            $tr.append($('<td/>')
                // KDHTODO remove inline styles
                .append($('<div style="width:44px;float:left;margin-right:10px;"/>')
                    .append('<img class="avatar" src="' + getUserAvatar(user) + '"/>')
                )
                .append($userDisplay = $('<div/>')
                    .append(user.username + '<br />')
                )
            );

            if (settings.viewOptions.showBadges) {
                // KDHTODO extract this logic to something outside the Board class
                // add trophies to the username display area
                if (user.wins) {
                    for (j=0; j<user.wins.length; j++) {
                        $userDisplay.append($('<div/>')
                            .addClass('winnertrophy-wrapper')
                            .data('pot', user.wins[j].pot)
                            .data('place', user.wins[j].place)
                            .data('year', user.wins[j].yr)
                            .data('money', user.wins[j].winnings)
                            .append('<img src="' + CONF.winnerTrophyUrlPrefix + user.wins[j].pot + user.wins[j].place + '.png" />')
                            .append($('<div/>')
                                .addClass('year pot' + user.wins[j].pot + ' place' + user.wins[j].place)
                                .append(shortenYear(user.wins[j].yr))
                            )
                        );
                    }
                }
                // add badges to the username display area
                if (user.userBadges) {
                    for (j=0; j<user.userBadges.length; j++) {
                        $userDisplay.append($('<img />')
                            .addClass('user-badge')
                            .attr('src', user.userBadges[j].badge.img)
                            .attr('alt', user.userBadges[j].display)
                            // KDHTODO instead of all this data crap, can we just put the whole badge in the data?
                            .data('info', user.userBadges[j].display)
                            .data('name', user.userBadges[j].badge.name)
                            .data('year', user.userBadges[j].yr)
                            .data('tagline', user.userBadges[j].badge.display)
                            .data('description', user.userBadges[j].badge.description)
                            .data('unlockedyear', user.userBadges[j].badge.unlocked_year)
                            .data('unlockeduser', user.userBadges[j].badge.unlockedBy ? user.userBadges[j].badge.unlockedBy.username : '')
                            .data('img', user.userBadges[j].badge.img)
                            .data('type', user.userBadges[j].badge.type)
                            .data('points', user.userBadges[j].badge.power_points)
                        );
                    }
                }
            }
            
            // show picks for this user
            if (settings.viewOptions.collapseHistory) {
                $tr.append('<td align="center">' + getOldRecord(user) + '</td>');
            }
            for (j=startWeek; j<=21; j++) {
                pick = getPick(user, j);
                if (pick) {
                    $tr.append($('<td/>')
                        .addClass(pick.incorrect == 1 ? 'incorrect' : '')
                        // KDHTODO add a mov-wrapper class instead of an inline style
                        .append($('<div style="position:relative;"/>')
                            .append(!settings.viewOptions.showMov || j > settings.currentWeek ? '' : $('<div/>')
                                .addClass('pickMov')
                                .addClass(pick.week < settings.currentWeek || pick.year < settings.currentYear ? 'old' : '')
                                .addClass(pick.incorrect ? 'incorrect' : '')
                                .html(stylizeMov(pick.mov && pick.mov.hasOwnProperty('mov') ? pick.mov.mov : ''))
                            )
                            .append(!settings.viewOptions.showLogos ? pick.team.shortname : $('<div/>')
                                .addClass('logo')
                                .addClass('logo-' + getTeamLogoClass(pick))
                                .css('background-position', getTeamLogoOffset(pick.team, 'small'))
                                .attr('title', pick.team.longname + (pick.setbysystem ? ' (Set by System)' : ''))
                            )
                        )
                    );
                } else {
                    $tr.append($('<td>&nbsp;</td>'));
                }
            }
            $tbody.append($tr);
                
        }
        
        $table = self.getTable();
        $table.empty().append($thead).append($tbody);
        self.buildPopovers();
        drawn = true;
    };

    this.buildPopovers = function() {
        // KDHTODO move this popover stuff somewhere else?
        // KDHTODO draw the popups using Yii partials
        var fnHideAll, fnGetTrophyData, fnGetBadgeData;

        fnHideAll = function() {
            $('.winnertrophy-wrapper', self.getTable()).add('.user-badge', self.getTable()).not(this).popover('hide');
        };

        fnGetTrophyData = function() {
            var $this = $(this),
                data = {
                    year:  $this.data('year'),
                    pot:   $this.data('pot'),
                    place: $this.data('place'),
                    money: $this.data('money')
                };
            return data;
        };

        fnGetBadgeData = function() {
            var $this = $(this),
                data = {
                    name:         $this.data('name'),
                    img:          $this.data('img'),
                    type:         $this.data('type'),
                    points:       $this.data('points'),
                    info:         $this.data('info'),
                    year:         $this.data('year'),
                    tagline:      $this.data('tagline'),
                    description:  $this.data('description'),
                    unlockedYear: $this.data('unlockedyear'),
                    unlockedUser: $this.data('unlockeduser')
                };
            return data;
        };
        
        // popovers for winner trophies
        $('.winnertrophy-wrapper', self.getTable()).on('click', function() {
            fnHideAll.call(this);
        }).popover({
            html: true,
            title: function() {
                var data = fnGetTrophyData.call($(this)),
                    content;
                // KDHTODO see if the icon's negative margin still works on other devices
                content = '<div class="icon"><img src="/images/badges/winnerbadge-' + data.pot + data.place + '.png" /></div>';
                content += (data.place == 1 ? 'First' : 'Second') + ' Place';
                return content;
            },
            content: function() {
                var data = fnGetTrophyData.call($(this)),
                    content;

                content = '';
                content += '<div class="type-label">Winner Trophy</div>';
                content += '<table class="table table-condensed small popover-table">';
                content += '<tr><td>Year</td><td>' + data.year + '</td></tr>';
                content += '<tr><td>Place</td><td>' + (data.place == 1 ? '1st' : '2nd') + '</td></tr>';
                content += '<tr><td>Pot</td><td>' + globals.getPotName(data.pot) + '</td></tr>';
                content += '<tr><td>Won</td><td>' + globals.dollarFormat(data.money) + '</td></tr>';
                content += '</table>';
                // KDHTODO show the number of power points it's worth
                // KDHTODO also get the record (pot2) or week of incorrect (pot1), or sum of MOV (pot3) for additional detail?
                return content;
            },
            placement: 'auto top'
        });

        // popovers for user badges
        $('.user-badge', self.getTable()).on('click', function() {
            fnHideAll.call(this);
        }).popover({
            html: true,
            title: function() {
                var data = fnGetBadgeData.call($(this)),
                    content;
                // KDHTODO see if the icon's negative margin still works on other devices
                content = '<div class="icon"><img src="' + data.img + '" /></div>' + data.name;
                return content;
            },
            content: function() {
                var data = fnGetBadgeData.call($(this)),
                    content;

                content = '';
                content += '<div class="type-label">User Badge</div>';
                content += (data.tagline && data.name != data.tagline ? '<small><em>' + data.tagline + '</em></small>' : '');
                content += '<table class="table table-condensed small popover-table">';
                content += (data.year ? '<tr><td>Awarded</td><td>' + data.year + '</td></tr>' : '');
                content += (data.info ? '<tr><td>Detail</td><td>' + data.info + '</td></tr>' : '');
                content += '<tr class="separator"><td>Type</td><td>' + data.type + '</td></tr>';
                // KDHTODO turn unlocked user into a link to their profile page
                content += (data.unlockedYear || data.unlockedUser ? '<tr><td>Unlocked</td><td>' + (data.unlockedYear ? data.unlockedYear : '') + (data.unlockedUser ? ' by <a href="#">' + data.unlockedUser + '</a>' : '') + '</td></tr>' : '');
                content += '<tr><td>Power&nbsp;Points</td><td>' + data.points + '</td></tr>';
                content += '<tr><td>Description</td><td>' + data.description + '</td></tr>';
                content += '</table>';
                return content;
            },
            placement: 'auto top'
        });
    };

    this.poll = function() {
        if (settings.poll && !polling) {
            polling = true;
            if (!drawn) {
                $table = self.getTable();
                $table.empty().append('<tbody><tr><td>Loading...</td></tr></tbody>');
            }
            $.ajax({
                url:        CONF.url.poll,
                data:       {
                                '_': new Date().getTime()
                            },
                type:       'get',
                cache:      false,
                success:    function(r) {
                                var boardString;
                                if (r && r.board) {
                                    boardString = JSON.stringify(r.board);
                                    if (lastBoard !== boardString) {
                                        lastBoard = boardString;
                                        settings.board = r.board;
                                        self.sort();
                                        self.redraw();
                                    }
                                }
                                setTimeout(self.poll, CONF.boardPollerInterval);
                            },
                complete:   function() {
                                polling = false;
                            },
                dataType:   'json'
            });
        }
    };

    if (settings.table instanceof jQuery) {
        $table = settings.table;
    } else if (typeof settings.table == 'string') {
        $table = $(settings.table);
    } else if (typeof settings.table == 'function') {
        $table = settings.table;
    } else {
        $.error('Invalid table specified');
    }

    if (settings.poll) {
        self.poll();
    }
};
$(function() {
    window.board = new Board({
        table: $('#pick-board'),
        poll:  true
    });
});

</script>


<div class="container">
    <h4>Most Recent Talk</h4>
    <?php
    foreach ($talk as $t) {
        $this->renderPartial('//_partials/talk', array('talk'=>$t));
    }
    ?>
</div>
<div class="container-fluid">
    
    <h5>Debug Current Week / Header Week: <?php echo $currentWeek;?> / <?php echo getHeaderWeek();?></h5>
    
    <div class="table-responsive">
        <table class="picks table table-striped table-bordered" id="pick-board">
        </table>
    </div>
    
</div>