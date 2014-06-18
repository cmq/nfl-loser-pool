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
                collapseHistory: false,
                showBadges:      true,
                showMov:         true,
                showLogos:       true
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
            key = key.substring(1);
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
    
    function getSortOrder(order, currentOrder) {
        var wasReversed = false;
        if (currentOrder.indexOf('-') === 0) {
            currentOrder = currentOrder.substring(1);
            wasReversed = true;
        }
        if (currentOrder == order) {
            if (wasReversed) {
                return order;
            }
            return '-' + order;
        }
        return order;
    }
    
    
    this.getTable = function() {
        if (typeof $table == 'function') {
            return $table();
        }
        return $table;
    };
    
    this.getBoard = function() {
        return settings.board;
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

        // KDHTODO instead of old way, where there tries to be 1 column for who's ahead, let there be a column for every pot, and a final column for total money

        // set up the primary table header
        $tr = $('<tr/>')
            .addClass('sortheads')
            .append($('<th colspan="2"/>')
                .html('User')
                .on('click', function(e) {
                    e.preventDefault();
                    self.sort(getSortOrder('username', settings.order), true);
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
                .on('click', (function(i) {
                    return function(e) {
                        e.preventDefault();
                        self.sort(getSortOrder('pick' + i, settings.order), true);
                    };
                })(i))
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
                        self.sort(getSortOrder('pick' + i, settings.order), true);
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
                    .append('<a href="' + CONF.url.profile(user.id) + '">' + user.username + (CONF.isSuperadmin ? ' (' + user.id + ')' : '') + '</a><br />')
                )
            );

            if (settings.viewOptions.showBadges) {
                buildTrophyCase(user, $userDisplay);
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
        globals.lightboxAvatars();  // re-activate the newly-drawn avatars as lightboxes
        drawn = true;
    };

    this.poll = function() {
        if (!polling) {
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
                                if (settings.poll) {
                                    setTimeout(self.poll, CONF.boardPollerInterval);
                                }
                            },
                complete:   function() {
                                polling = false;
                            },
                dataType:   'json'
            });
        } else {
            setTimeout(self.poll, 1000);
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

    self.poll();
};