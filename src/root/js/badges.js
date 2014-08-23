function buildTrophyCase(user, $container) {
    var fnBuildPopovers, j, alt;
    
    fnBuildPopovers = function() {
        $(function() {
            globals.buildBadgePopovers($container);
        });
    };
    
    // make sure the color displays properly
    $container.addClass('trophycase');
    
    // add trophies to the username display area
    if (user.wins) {
        for (j=0; j<user.wins.length; j++) {
            alt = (user.wins[j].place==1 ? '1st' : '2nd') + ' Place, ' + globals.getPotName(user.wins[j].pot) + ' Pot, ' + user.wins[j].yr;
            $container.append($('<div/>')
                .addClass('winnertrophy-wrapper spawns-popover')
                .data('win', user.wins[j])
                .append('<img src="' + CONF.winnerTrophyUrlPrefix + user.wins[j].pot + user.wins[j].place + '.png" title="' + alt + '" alt="' + alt + '" />')
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
                .attr('title', user.userBadges[j].display)
                .data('userBadge', user.userBadges[j])
            );
        }
    }
    
    fnBuildPopovers();
}