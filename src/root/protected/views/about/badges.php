<script>
var trophies = <?php echo CJSON::encode($trophies);?>,
    trophiesUnlocked = <?php echo CJSON::encode($trophiesUnlocked);?>,
    badges = <?php echo CJSON::encode($badges);?>;


$(function() {
    var $table = $('#badges'),
        i, unlocked;

    function trophyName(pot, place) {
        pot   = parseInt(pot, 10);
        place = parseInt(place, 10);
        switch (pot) {
            case 1:
                return (place == 1 ? 'Blue' : 'Red') + ' Flame';
            case 2:
                return (place == 1 ? 'Gold/Blue' : 'Silver/Red') + ' Medal';
            case 3:
                return (place == 1 ? 'Blue-Loinclothed' : 'Red-Loinclothed') + ' Sumo';
        }
        return '';
    }

    function trophyUnlockedYear(pot, place) {
        var i;
        for (i=0; i<trophiesUnlocked.length; i++) {
            if (trophiesUnlocked[i].pot == pot && trophiesUnlocked[i].place == place) {
                return trophiesUnlocked[i].yr;
            }
        }
        return 'N/A';
    }

    function trophyUnlockedPlayers(pot, place) {
        var i, players = '';
        for (i=0; i<trophiesUnlocked.length; i++) {
            if (trophiesUnlocked[i].pot == pot && trophiesUnlocked[i].place == place) {
                players += globals.avatarBubble(trophiesUnlocked[i]);
            }
        }
        return players;
    }

    function trophyDescription(pot, place) {
        pot   = parseInt(pot, 10);
        place = parseInt(place, 10);
        return 'A <strong>' + (globals.ordinal(place)) + ' place</strong> finish in the <strong>' + globals.getPotName(pot) + '</strong> pot.';
    }
    
    for (i=0; i<trophies.length; i++) {
        $table.append($('<tr/>')
            .append($('<td class="text-center"/>')
                .append($('<div/>')
                    .addClass('winnertrophy-wrapper spawns-popover')
                    .data('win', trophies[i])
                    .append('<img src="' + CONF.winnerTrophyUrlPrefix + trophies[i].pot + trophies[i].place + '.png" />')
                    .append($('<div/>')
                        .addClass('year pot' + trophies[i].pot + ' place' + trophies[i].place)
                        .append(globals.shortenYear(trophies[i].yr))
                    )
                )
            )
            .append('<td class="text-center">' + trophyName(trophies[i].pot, trophies[i].place) + '</td>')
            .append('<td class="text-center">Trophy</td>')
            .append('<td class="text-center">Yes</td>')
            .append('<td class="text-center">Yes</td>')
            .append('<td class="text-right">' + (trophies[i].pot == 1 ? CONF.powerMultipliers.pointsPerFirstPlace : CONF.powerMultipliers.pointsPerSecondPlace).toFixed(1) + '</td>')
            .append('<td class="text-center">' + trophyUnlockedYear(trophies[i].pot, trophies[i].place) + '</td>')
            .append('<td class="text-center">' + trophyUnlockedPlayers(trophies[i].pot, trophies[i].place) + '</td>')
            .append('<td>' + trophyDescription(trophies[i].pot, trophies[i].place) + '</td>')
        );
    }

    for (i=0; i<badges.length; i++) {
        unlocked = badges[i].unlocked_year && badges[i].unlockedBy;
        $table.append($('<tr/>')
                .append('<td class="text-center"><img src="' + badges[i].img + '" /></td>')     // KDHTODO does this need more classes or need to be clickable?
                .append('<td class="text-center">' + badges[i].name + '</td>')
                .append('<td class="text-center">' + badges[i].type + '</td>')
                .append('<td class="text-center">' + (badges[i].permanent ? 'Yes' : 'No') + '</td>')
                .append('<td class="text-center">' + (badges[i].replicable ? 'Yes' : 'No') + '</td>')
                .append('<td class="text-right">' + badges[i].power_points.toFixed(1) + '</td>')
                .append('<td class="text-center"' + (unlocked ? '' : ' colspan="2"') + '>' + (unlocked ? badges[i].unlocked_year + '</td><td class="text-center">' + globals.avatarBubble(badges[i].unlockedBy) : '<strong>LOCKED</strong>') + '</td>')
                .append('<td>' + badges[i].description + '</td>')
            );
        }
    
    globals.lightboxAvatars();
    globals.buildBadgePopovers();
});
</script>
<div class="container">
    <h2>Trophies/Badges</h2>
    <p>
        Trohpies and Badges are accolades that can be collected by players for various feats of greatness.  Most trophies
        and badges are permanent, but some contain requirements that must be met in order to retain them.  All trophies
        and many badges also award Power Points which count towards the player's Power Ranking.
    </p>
    <p>
        <strong>Trophies</strong> are badges players win by finishing in 1st or 2nd place in any of the pots.  Trophies will
        display the year they were won inside them and cannot be lost.  Clicking on a player's trophy will open a window with
        details about when they won the trophy and how much money it was worth to them.
    </p>
    <p>
        <strong>Badges</strong> are awards given to players for various accomplishments or other random events.  There are
        three types of badges with different properties:
        <ul>
            <li><strong>Unique</strong> - special badges earned for unique scenarios.  Unique badges are permanent and
            cannot be earned by any specific player actions.</li>
            <li><strong>Earnable</strong> - badges that can be earned by fulfilling their requirements.  Multiple copies
            of these badges could potentially be earned by fulfilling their requirements multiple times.  Some earnable
            badges are permanent, while others have minimum qualifications that must be maintained in order to keep them.</li>
            <li><strong>Floating</strong> - each floating badge has only a single copy which can only be possessed by
            a single player at a time.  Each week, floating badges are recalculated and will move to the players who best
            meet their qualifications</li>
        </ul>
    </p>
    <p>
        A list of Trophies and Badges is presented below.  This list includes all discovered badges, as well as teasers
        for a few that have not yet been discovered and must be unlocked by fulfilling their requirements.
        <table class="table table-condensed table-striped table-bordered">
            <thead>
                <tr>
                    <th class="text-center" colspan="2">Trophy/Badge</th>
                    <th class="text-center">Type</th>
                    <th class="text-center">Permanent?</th>
                    <th class="text-center">Replicable?</th>
                    <th class="text-center">Power Points</th>
                    <th class="text-center" colspan="2">Unlocked Year/Player(s)</th>
                    <th class="text-center">Description</th>
                </tr>
            </thead>
            <tbody id="badges">
            </tbody>
        </table>
    </p>
</div>
