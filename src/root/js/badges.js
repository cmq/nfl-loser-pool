function buildTrophyCase(user, $container) {
    var fnBuildPopovers, j;
    
    fnBuildPopovers = function() {
        $(function() {
            // KDHTODO draw the popups using Yii partials
            var fnHideAll;

            fnHideAll = function() {
                $('.spawns-popover').not(this).popover('hide');
                // next line is to fix a bug with the popover plugin (@see https://github.com/twbs/bootstrap/issues/10568)
                $('.popover:not(.in)').hide().detach();
            };

            // popovers for winner trophies
            $('.winnertrophy-wrapper', $container).on('click', function() {
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
            $('.user-badge', $container).on('click', function() {
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
                    content += (userBadge.badge.unlocked_year || unlockedUser ? '<tr><td>Unlocked</td><td>' + (userBadge.badge.unlocked_year ? userBadge.badge.unlocked_year : '') + (unlockedUser ? ' by <a href="' + CONF.url.profile(userBadge.badge.unlockedBy.id) + '">' + unlockedUser + '</a>' : '') + '</td></tr>' : '');
                    content += '<tr><td>Power&nbsp;Points</td><td>' + userBadge.badge.power_points + '</td></tr>';
                    content += '<tr><td>Description</td><td>' + userBadge.badge.description + '</td></tr>';
                    content += '</table>';
                    return content;
                },
                placement: 'auto top'
            });
        });
    };
    
    // make sure the color displays properly
    $container.addClass('trophycase');
    
    // add trophies to the username display area
    if (user.wins) {
        for (j=0; j<user.wins.length; j++) {
            $container.append($('<div/>')
                .addClass('winnertrophy-wrapper spawns-popover')
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
            $container.append($('<img />')
                .addClass('user-badge spawns-popover')
                .attr('src', user.userBadges[j].badge.img)
                .attr('alt', user.userBadges[j].display)
                .data('userBadge', user.userBadges[j])
            );
        }
    }
    
    fnBuildPopovers();
}