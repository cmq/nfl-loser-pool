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
                .append($('<div/>')
                    .addClass('avatar-wrapper')
                    .append('<img class="avatar" src="' + globals.getUserAvatar(user) + '"/>')
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
                            .data('win', user.wins[j])
                            .append('<img src="' + CONF.winnerTrophyUrlPrefix + user.wins[j].pot + user.wins[j].place + '.png" />')
                            .append($('<div/>')
                                .addClass('year pot' + user.wins[j].pot + ' place' + user.wins[j].place)
                                .append(globals.shortenYear(user.wins[j].yr))
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
                            .data('userBadge', user.userBadges[j])
                        );
                    }
                }
            }
            
            // show picks for this user
            if (settings.viewOptions.collapseHistory) {
                $tr.append('<td align="center">' + globals.getOldRecord(user, settings.currentWeek) + '</td>');
            }
            for (j=startWeek; j<=21; j++) {
                pick = getPick(user, j);
                if (pick) {
                    $tr.append($('<td/>')
                        .addClass(pick.incorrect == 1 ? 'incorrect' : '')
                        .append($('<div/>')
                            .addClass('pick-wrapper')
                            .append(!settings.viewOptions.showMov || j > settings.currentWeek ? '' : $('<div/>')
                                .addClass('pickMov')
                                .addClass(pick.week < settings.currentWeek || pick.year < settings.currentYear ? 'old' : '')
                                .addClass(pick.incorrect ? 'incorrect' : '')
                                .html(stylizeMov(pick.mov && pick.mov.hasOwnProperty('mov') ? pick.mov.mov : ''))
                            )
                            .append(!settings.viewOptions.showLogos ? pick.team.shortname : $('<div/>')
                                .addClass('logo')
                                .addClass('logo-' + globals.getTeamLogoClass(pick, settings.currentWeek))
                                .css('background-position', globals.getTeamLogoOffset(pick.team, 'small'))
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
        var fnHideAll;

        fnHideAll = function() {
            $('.winnertrophy-wrapper', self.getTable()).add('.user-badge', self.getTable()).not(this).popover('hide');
        };

        // popovers for winner trophies
        $('.winnertrophy-wrapper', self.getTable()).on('click', function() {
            fnHideAll.call(this);
        }).popover({
            html: true,
            title: function() {
                var win = $(this).data('win'),
                    content;
                // KDHTODO see if the icon's negative margin still works on other devices
                content = '<div class="icon"><img src="/images/badges/winnerbadge-' + win.pot + win.place + '.png" /></div>';
                content += (win.place == 1 ? 'First' : 'Second') + ' Place';
                return content;
            },
            content: function() {
                var win = $(this).data('win'),
                    content;

                content = '';
                content += '<div class="type-label">Winner Trophy</div>';
                content += '<table class="table table-condensed small popover-table">';
                content += '<tr><td>Year</td><td>' + win.yr + '</td></tr>';
                content += '<tr><td>Place</td><td>' + (win.place == 1 ? '1st' : '2nd') + '</td></tr>';
                content += '<tr><td>Pot</td><td>' + globals.getPotName(win.pot) + '</td></tr>';
                content += '<tr><td>Won</td><td>' + globals.dollarFormat(win.winnings) + '</td></tr>';
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
                var userBadge = $(this).data('userBadge'),
                    content;
                // KDHTODO see if the icon's negative margin still works on other devices
                content = '<div class="icon"><img src="' + userBadge.badge.img + '" /></div>' + userBadge.badge.name;
                return content;
            },
            content: function() {
                var userBadge = $(this).data('userBadge'),
                    unlockedUser = (userBadge.badge.unlockedBy && userBadge.badge.unlockedBy.username ? userBadge.badge.unlockedBy.username : null),
                    content;

                content = '';
                content += '<div class="type-label">User Badge</div>';
                content += (userBadge.badge.display && userBadge.badge.name != userBadge.badge.display ? '<small><em>' + userBadge.badge.display + '</em></small>' : '');
                content += '<table class="table table-condensed small popover-table">';
                content += (userBadge.yr ? '<tr><td>Awarded</td><td>' + userBadge.yr + '</td></tr>' : '');
                content += (userBadge.display ? '<tr><td>Detail</td><td>' + userBadge.display + '</td></tr>' : '');
                content += '<tr class="separator"><td>Type</td><td>' + userBadge.badge.type + '</td></tr>';
                // KDHTODO turn unlocked user into a link to their profile page
                content += (userBadge.badge.unlocked_year || unlockedUser ? '<tr><td>Unlocked</td><td>' + (userBadge.badge.unlocked_year ? userBadge.badge.unlocked_year : '') + (unlockedUser ? ' by <a href="#">' + unlockedUser + '</a>' : '') + '</td></tr>' : '');
                content += '<tr><td>Power&nbsp;Points</td><td>' + userBadge.badge.power_points + '</td></tr>';
                content += '<tr><td>Description</td><td>' + userBadge.badge.description + '</td></tr>';
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