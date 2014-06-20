function Board(options) {
    var self = this,
        settings = $.extend(true, {
            container:   null,
            order:       'money',
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
        pots = [[{"money":0,"users":[]},{"money":0,"users":[]}],[{"money":0,"users":[]},{"money":0,"users":[]}],[{"money":0,"users":[]},{"money":0,"users":[]}]],   // first index is 0-based pot, next index is 0-based place, so "pots[1][0]" is "pot 2, first place"
        polling = false,
        drawn = false,
        lastBoard = JSON.stringify(settings.board),
        $container;


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
    
    function getSortHeadClass(key) {
        var classes = [];
        key = key.toLowerCase();
        if (key === settings.order.replace('-', '')) {
            classes.push('active-sort');
            if (settings.order.indexOf('-') === 0) {
                classes.push('reverse');
            }
        }
        return classes.join(' ');
    }

    function getSortVal(user, key) {
        var week, wl, w, p, l;
        key = key.toLowerCase();
        if (key.indexOf('-') === 0) {
            key = key.substring(1); // a "-" in the front of a sort key means we're doing a reverse sort
        }
        if (key.indexOf('pick') === 0) {
            week = parseInt(key.substring(4), 10);
            return getPickName(user, week, true).toLowerCase();
        }
        switch (key.toLowerCase()) {
            case 'record':
                wl = user.record.split('-');
                p = 0;
                if (wl.length == 2) {
                    w = parseFloat(wl[0], 10);
                    l = parseFloat(wl[1], 10);
                    if (!isNaN(w) && !isNaN(l)) {
                        if (w+l > 0) {
                            if (l > 0) {
                                if (w > 0) {
                                    p = w/(w+l);
                                } else {
                                    p = 0 - (l*.1); // subtract .1 for every loss so 0-1 will outrank 0-2
                                }
                            } else {
                                p = 1 + (w*.1); // add .1 for every win so 2-0 will outrank 1-0
                            }
                        } else {
                            // they are 0-0 -- they should actually show up higher than someone who's 0-1
                            // so return something so low they can't normally get to it, but still higher than 0
                            p = .00001;
                        }
                    }
                }
                return 10-p;    // invert search by default
            case 'stayalive':
                return user.stayAlive * -1;
            case 'margin':
                return user.margin * -1;
            case 'powerranking':
                return parseInt(user.power_ranking);
            case 'money':
                return user.money * -1;
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
    
    function aggregateStats() {
        var i, j, k, l, user, pick, firstWeek, correct, incorrect, margin,
            mostCorrect         = -2,
            secondMostCorrect   = -2,
            firstWeekWrong      = 0,
            secondWeekWrong     = 0,
            highestMargin       = 0,
            secondHighestMargin = 0;
        
        // figure out pick stats
        for (i=0; i<settings.board.length; i++) {
            user      = settings.board[i];
            firstWeek = 22;
            correct   = 0;
            incorrect = 0;
            margin    = 0;
            for (j=1; j<=21; j++) {
                pick = getPick(user, j);
                if (pick) {
                    if (pick.week > settings.currentWeek && settings.currentYear >= CONF.currentYear) {
                        // we hit a week that is beyond the current year/week, so we can't count it, and we're done with this user
                        break;
                    }
                    margin -= pick.mov && pick.mov.hasOwnProperty('mov') ? parseInt(pick.mov.mov, 10) : 0;
                    if (pick.incorrect == 1) {
                        if (firstWeek === 22) {
                            firstWeek = parseInt(pick.week, 10);
                        }
                        incorrect++;
                    } else {
                        correct++;
                    }
                }
            }
            settings.board[i].correct   = correct;
            settings.board[i].record    = correct + '-' + incorrect;
            settings.board[i].stayAlive = firstWeek;
            settings.board[i].margin    = margin;
            settings.board[i].money     = 0;    // we'll figure this out below
            if (correct > secondMostCorrect) {
                if (correct > mostCorrect) {
                    secondMostCorrect = mostCorrect;
                    mostCorrect       = correct;
                } else if (correct != mostCorrect) {
                    secondMostCorrect = correct;
                }
            }
            if (firstWeek > secondWeekWrong) {
                if (firstWeek > firstWeekWrong) {
                    secondWeekWrong = firstWeekWrong;
                    firstWeekWrong  = firstWeek;
                } else if (firstWeek != firstWeekWrong) {
                    secondWeekWrong = firstWeek;
                }
            }
            if (margin > secondHighestMargin) {
                if (margin > highestMargin) {
                    secondHighestMargin = highestMargin;
                    highestMargin       = margin;
                } else if (margin != highestMargin) {
                    secondHighestMargin = margin;
                }
            }
        }
        
        // figure out the pots
        if (settings.currentYear == CONF.currentYear) {
            // reset the users on each pot and each place
            for (i=0; i<3; i++) {
                for (j=0; j<2; j++) {
                    pots[i][j].users = [];
                }
            }
            // figure out the users belonging to each pot and place
            for (i=0; i<settings.board.length; i++) {
                // pot 1
                if (settings.board[i].stayAlive === firstWeekWrong) {
                    pots[0][0].users.push(settings.board[i]);
                }
                if (settings.board[i].stayAlive === secondWeekWrong) {
                    pots[0][1].users.push(settings.board[i]);
                }
                // pot 2
                if (settings.board[i].correct === mostCorrect) {
                    pots[1][0].users.push(settings.board[i]);
                }
                if (settings.board[i].correct === secondMostCorrect) {
                    pots[1][1].users.push(settings.board[i]);
                }
                // pot 3
                if (settings.board[i].margin === highestMargin) {
                    pots[2][0].users.push(settings.board[i]);
                }
                if (settings.board[i].margin === secondHighestMargin) {
                    pots[2][1].users.push(settings.board[i]);
                }
            }
            // figure out the money each pot and place is worth
            for (i=0; i<3; i++) {
                if (pots[i][0].users.length + pots[i][1].users.length > 0) {
                    pots[i][1].money = (settings.board.length * (i==2 ? CONF.movFee : CONF.entryFee/2)) / ((pots[i][0].users.length * 2) + pots[i][1].users.length);
                }
                pots[i][0].money = pots[i][1].money * 2;
            }
            // add the user's money to their record
            for (i=0; i<3; i++) {
                for (j=0; j<2; j++) {
                    for (k=0; k<pots[i][j].users.length; k++) {
                        for (l=0; l<settings.board.length; l++) {
                            if (settings.board[l].id == pots[i][j].users[k].id) {
                                settings.board[l].money += pots[i][j].money;
                                break;
                            }
                        }
                    }
                }
            }
        } else {
            // KDHTODO this is a past year, the values should be provided from the server
            settings.board[i].money = user.id;  // KDHTODO replace with real logic
        }
        
    }
    
    
    this.getContainer = function() {
        if (typeof $container == 'function') {
            return $container();
        }
        return $container;
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
        var $container, $table, $tr, i, j, user, pick, $userDisplay,
            startWeek = settings.viewOptions.collapseHistory ? settings.currentWeek : 1,
            $payout   = null,
            $thead    = $('<thead/>'),
            $tbody    = $('<tbody/>');

        // set up the responsive table, per bootstrap
        $table = $('<table class="picks table table-striped table-bordered" />');
        
        // set up the primary table header
        $tr = $('<tr/>')
            .addClass('sortheads')
            .append($('<th colspan="2"/>')
                .addClass(getSortHeadClass('username'))
                .html('User')
                .on('click', function(e) {
                    e.preventDefault();
                    self.sort(getSortOrder('username', settings.order), true);
                })
            );
        if (settings.viewOptions.collapseHistory) {
            $tr.append($('<th/>')
                //.addClass(getSortHeadClass('historicalRecord'))   // KDHTODO implement
                .html('Weeks 1 - ' + (settings.currentWeek-1))
                .on('click', function(e) {
                    e.preventDefault();
                    // KDHTODO implement sorting by early-season record
                })
            );
        }
        // KDHTODO whichever heading is the SORT heading should be highlighted in some way (I am adding classes "active-sort" and optionally "reverse", but need to CSS them somehow)
        for (i=startWeek; i<=21; i++) {
            $tr.append($('<th/>')
                .addClass(getSortHeadClass('pick' + i))
                .html(globals.getWeekName(i))
                .on('click', (function(i) {
                    return function(e) {
                        e.preventDefault();
                        self.sort(getSortOrder('pick' + i, settings.order), true);
                    };
                })(i))
            );
        }
        $tr.append($('<th/>')
            .addClass(getSortHeadClass('stayAlive'))
            .html('Pot 1<br />(Alive)')
            .on('click', function(e) {
                e.preventDefault();
                self.sort(getSortOrder('stayAlive', settings.order), true);
            })
        );
        $tr.append($('<th/>')
            .addClass(getSortHeadClass('record'))
            .html('Pot 2<br />(Record)')
            .on('click', function(e) {
                e.preventDefault();
                self.sort(getSortOrder('record', settings.order), true);
            })
        );
        $tr.append($('<th/>')
            .addClass(getSortHeadClass('margin'))
            .html('Pot 3<br />(Margin)')
            .on('click', function(e) {
                e.preventDefault();
                self.sort(getSortOrder('margin', settings.order), true);
            })
        );
        $tr.append($('<th/>')
            .addClass(getSortHeadClass('money'))
            .html(settings.currentYear + ' $')
            .on('click', function(e) {
                e.preventDefault();
                self.sort(getSortOrder('money', settings.order), true);
            })
        );
        $tr.append($('<th colspan="2"/>')
            .addClass(getSortHeadClass('powerRanking'))
            .html('Power<br />Rank/Pts')
            .on('click', function(e) {
                e.preventDefault();
                self.sort(getSortOrder('powerRanking', settings.order), true);
            })
        );
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
            $tr.append('<th colspan="6"/>');
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
            $tr.append($('<td nowrap="nowrap"/>')
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
                $tr.append('<td class="text-center">' + globals.getOldRecord(user, settings.currentWeek) + '</td>');
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
                                .html(stylizeMov(pick.mov && pick.mov.hasOwnProperty('mov') ? pick.mov.mov : ''))   // KDHTODO does this use the viewOptions correctly?
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
            
            // show the end columns
            $tr.append('<td class="text-center">' + (user.stayAlive > 21 ? '...' : 'Week ' + user.stayAlive) + '</td>');
            $tr.append('<td class="text-center">' + user.record + '</td>');
            $tr.append('<td class="text-right">' + user.margin + '</td>');
            $tr.append('<td class="text-right">' + globals.dollarFormat(user.money) + '</td>');
            $tr.append('<td class="text-right">' + globals.ordinal(user.power_ranking) + '</td>');
            $tr.append('<td class="text-right">' + user.power_points + '</td>');
            
            $tbody.append($tr);
                
        }
        
        
        // draw the pots payout
        // KDHTODO clean up this display
        if (settings.currentYear == CONF.currentYear) {
            $payout = $('<div />');
            for (i=0; i<pots.length; i++) {
                $payout.append('Pot ' + (i+1) + ':<br /> 1st (' + globals.dollarFormat(pots[i][0].money) + '):');
                for (j=0; j<pots[i][0].users.length; j++) {
                    $payout.append(' ' + pots[i][0].users[j].username);
                }
                $payout.append('<br />2nd (' + globals.dollarFormat(pots[i][1].money) + '):');
                for (j=0; j<pots[i][1].users.length; j++) {
                    $payout.append(' ' + pots[i][1].users[j].username);
                }
                $payout.append('<br /><br />');
            }
        }
        
        
        // show all the output
        $table.append($thead).append($tbody);
        $container = self.getContainer();
        $container
            .empty()
            .append($payout)
            .append($('<div class="table-responsive"/>')
                .append($table)
            );
        globals.lightboxAvatars();  // re-activate the newly-drawn avatars as lightboxes
        drawn = true;
    };

    this.poll = function() {
        if (!polling) {
            polling = true;
            if (!drawn) {
                $container = self.getContainer();
                $container.empty().append('<div class="table-responsive"><table class="picks table table-striped table-bordered"><tbody><tr><td>Loading...</td></tr></tbody></table></div>');
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
                                        aggregateStats();
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

    if (settings.container instanceof jQuery) {
        $container = settings.container;
    } else if (typeof settings.container == 'string') {
        $container = $(settings.container);
    } else if (typeof settings.container == 'function') {
        $container = settings.container;
    } else {
        $.error('Invalid container specified');
    }
    
    if (settings.poll) {
        self.poll();
    } else {
        aggregateStats();
        self.sort();
        self.redraw();
    }
};