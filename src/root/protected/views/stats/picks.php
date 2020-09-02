<script>
var users           = <?php echo CJSON::encode($users);?>,
    picks           = <?php echo CJSON::encode($picks);?>,
    teams           = <?php echo CJSON::encode($teams);?>,
    teamsById       = {},
    usersById       = {},
    byUser          = {},
    byTeam          = {},
    mode            = 'byteam',
    showInactive    = false,
    showAllModes    = false,
    sortProp        = 'total',
    sortPropN       = 'total',
    sortRev         = true,
    expanded        = '';
    


function getSortProp(secondary) {
    var teamMode = (mode == 'byteam');
    if (sortProp === 'name') {
        // special case, since our user/team objects have different name properties
        return function(obj, key) {
            if (teamMode != secondary) {
                return teamsById[key].longname;
            } else {
                return usersById[key].username;
            }
        };
    } else {
        return sortProp;
    }
}

function getSortDescription() {
    var start = 'Viewed by ' + (mode == 'byteam' ? 'Team' : 'User') + ', Sorted by ';
    switch (sortPropN) {
        case 'name':
            return start + 'Team/User Name';
        case 'total':
            return start + 'Total Picks Made';
        case 'correct':
            return start + 'Total Correct Picks';
        case 'correctPercent':
            return start + 'Percentage of Picks Correct';
        case 'incorrect':
            return start + 'Total Incorrect Picks';
        case 'incorrectPercent':
            return start + 'Percentage of Picks Incorrect';
        case 'manual':
            return start + 'Total Manual Picks';
        case 'manualPercent':
            return start + 'Percentage of Picks Made Manually';
        case 'setBySystem':
            return start + 'Total Picks Set by System';
        case 'setBySystemPercent':
            return start + 'Percentage of Picks Set by System';
        case 'correctManual':
            return start + 'Total Correct Picks Made Manually';
        case 'correctManualPercent':
            return start + 'Percentage of Picks Correct and Made Manually';
        case 'correctSetBySystem':
            return start + 'Total Correct Picks Set by System';
        case 'correctSetBySystemPercent':
            return start + 'Percentage of Picks Correct but Set by System';
        case 'incorrectManual':
            return start + 'Total Incorrect Picks Made Manually';
        case 'incorrectManualPercent':
            return start + 'Percentage of Picks Incorrect and Made Manually';
        case 'incorrectSetBySystem':
            return start + 'Total Incorrect Picks Set by System';
        case 'incorrectSetBySystemPercent':
            return start + 'Percentage of Picks Incorrect and Set by System';
    }
}

function asPercentage(stat, key) {
    return ((stat[key] / stat.total) * 100).toFixed(1) + '%';
}

function newPickStat() {
    return {
        total:                  0,
        correct:                0,
        incorrect:              0,
        manual:                 0,
        setBySystem:            0,
        correctManual:          0,
        correctSetBySystem:     0,
        incorrectManual:        0,
        incorrectSetBySystem:   0
    };
}

function newByUser() {
    var i, n = newPickStat();
    n.teams = {};
    for (i=0; i<teams.length; i++) {
        n.teams[teams[i].id] = newPickStat();
    }
    return n;
}

function newByTeam() {
    var i, n = newPickStat();
    n.users = {};
    for (i=0; i<users.length; i++) {
        n.users[users[i].id] = newPickStat();
    }
    return n;
}

function applyPick(stat, pick) {
    var correct = (pick.incorrect == 0),
        manual  = (pick.setbysystem === 0);
    stat.total++;
    stat.correct                += (correct ? 1 : 0);
    stat.incorrect              += (correct ? 0 : 1);
    stat.manual                 += (manual  ? 1 : 0);
    stat.setBySystem            += (manual  ? 0 : 1);
    stat.correctManual          += (correct  && manual  ? 1 : 0);
    stat.correctSetBySystem     += (correct  && !manual ? 1 : 0);
    stat.incorrectManual        += (!correct && manual  ? 1 : 0);
    stat.incorrectSetBySystem   += (!correct && !manual ? 1 : 0);
}

function filter() {
    var i, pick;
    byUser = {};
    byTeam = {};
    for (i=0; i<picks.length; i++) {
        pick = picks[i];
        if (!showInactive && parseInt(usersById[pick.userid].active, 10) !== 1) continue;
        if (!showAllModes && parseInt(pick.hardcore, 10) !== <?php echo (isHardcoreMode() ? 1 : 0);?>) continue;
        if (pick.teamid > 0) {
            if (!byUser.hasOwnProperty(pick.userid)) {
                byUser[pick.userid] = newByUser();
            }
            applyPick(byUser[pick.userid], pick);
            applyPick(byUser[pick.userid].teams[pick.teamid], pick);
            
            if (!byTeam.hasOwnProperty(pick.teamid)) {
                byTeam[pick.teamid] = newByTeam();
            }
            applyPick(byTeam[pick.teamid], pick);
            applyPick(byTeam[pick.teamid].users[pick.userid], pick);
        }
    }
}

function drawHead(label, propN, prop, rev, stylizeIfActive, colspan, rowspan) {
    var $th = $('<th' + (typeof colspan !== 'undefined' ? ' colspan="' + colspan + '"' : '') + (typeof rowspan !== 'undefined' ? ' rowspan="' + rowspan + '"' : '') + '>' + label + '</th>')
        .addClass(sortPropN == propN && stylizeIfActive !== false ? 'active-sort' : '')
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
        teamMode = (mode == 'byteam'),
        primary = teamMode ? byTeam : byUser,
        secondary,
        $table = $('<table class="table table-condensed table-bordered table-striped" />'),
        $tbody = $('<tbody/>');

    $table.append($('<thead/>')
        .append($('<tr class="sortable"/>')
            .append(drawHead('Name', 'name', 'name', false, true, 1, 2))
            .append(drawHead('Total', 'total', 'total', true, true, 1, 2))
            .append(drawHead('Correct', 'correct', 'correct', true, false, 2))
            .append(drawHead('Incorrect', 'incorrect', 'incorrect', true, false, 2))
            .append(drawHead('Manual', 'manual', 'manual', true, false, 2))
            .append(drawHead('Set by System', 'setBySystem', 'setBySystem', true, false, 2))
            .append(drawHead('Correct Manual', 'correctManual', 'correctManual', true, false, 2))
            .append(drawHead('Correct Set by System', 'correctSetBySystem', 'correctSetBySystem', true, false, 2))
            .append(drawHead('Incorrect Manual', 'incorrectManual', 'incorrectManual', true, false, 2))
            .append(drawHead('Incorrect Set by System', 'incorrectSetBySystem', 'incorrectSetBySystem', true, false, 2))
        )
        .append($('<tr class="sortable" />')
            .append(drawHead('Num', 'correct', 'correct', true))
            .append(drawHead('%', 'correctPercent', function(obj) { return obj.total > 0 ? obj.correct / obj.total : 0; }, true))
            .append(drawHead('Num', 'incorrect', 'incorrect', true))
            .append(drawHead('%', 'incorrectPercent', function(obj) { return obj.total > 0 ? obj.incorrect / obj.total : 0; }, true))
            .append(drawHead('Num', 'manual', 'manual', true))
            .append(drawHead('%', 'manualPercent', function(obj) { return obj.total > 0 ? obj.manual / obj.total : 0; }, true))
            .append(drawHead('Num', 'setBySystem', 'setBySystem', true))
            .append(drawHead('%', 'setBySystemPercent', function(obj) { return obj.total > 0 ? obj.setBySystem / obj.total : 0; }, true))
            .append(drawHead('Num', 'correctManual', 'correctManual', true))
            .append(drawHead('%', 'correctManualPercent', function(obj) { return obj.total > 0 ? obj.correctManual / obj.total : 0; }, true))
            .append(drawHead('Num', 'correctSetBySystem', 'correctSetBySystem', true))
            .append(drawHead('%', 'correctSetBySystemPercent', function(obj) { return obj.total > 0 ? obj.correctSetBySystem / obj.total : 0; }, true))
            .append(drawHead('Num', 'incorrectManual', 'incorrectManual', true))
            .append(drawHead('%', 'incorrectManualPercent', function(obj) { return obj.total > 0 ? obj.incorrectManual / obj.total : 0; }, true))
            .append(drawHead('Num', 'incorrectSetBySystem', 'incorrectSetBySystem', true))
            .append(drawHead('%', 'incorrectSetBySystemPercent', function(obj) { return obj.total > 0 ? obj.incorrectSetBySystem / obj.total : 0; }, true))
        )
    ).append($tbody);

    globals.bySortedValue(primary, getSortProp(false), function(key, value) {
        if (primary[key].total > 0) {
            $tbody.append($('<tr />')
                .append($('<td nowrap="nowrap"/>')
                    .append(teamMode ? globals.getTeamLogo(teamsById[key], 'medium').css('float', 'left') : globals.avatarBubble(usersById[key], true))
                    .append(' (<a href="#" class="details-link" primaryid="' + key + '">Details</a>)')
                )
                .append('<td class="text-right">' + primary[key].total + '</td>')
                .append('<td class="text-right">' + primary[key].correct + '</td>')
                .append('<td class="text-right">' + asPercentage(primary[key], 'correct') + '</td>')
                .append('<td class="text-right">' + primary[key].incorrect + '</td>')
                .append('<td class="text-right">' + asPercentage(primary[key], 'incorrect') + '</td>')
                .append('<td class="text-right">' + primary[key].manual + '</td>')
                .append('<td class="text-right">' + asPercentage(primary[key], 'manual') + '</td>')
                .append('<td class="text-right">' + primary[key].setBySystem + '</td>')
                .append('<td class="text-right">' + asPercentage(primary[key], 'setBySystem') + '</td>')
                .append('<td class="text-right">' + primary[key].correctManual + '</td>')
                .append('<td class="text-right">' + asPercentage(primary[key], 'correctManual') + '</td>')
                .append('<td class="text-right">' + primary[key].correctSetBySystem + '</td>')
                .append('<td class="text-right">' + asPercentage(primary[key], 'correctSetBySystem') + '</td>')
                .append('<td class="text-right">' + primary[key].incorrectManual + '</td>')
                .append('<td class="text-right">' + asPercentage(primary[key], 'incorrectManual') + '</td>')
                .append('<td class="text-right">' + primary[key].incorrectSetBySystem + '</td>')
                .append('<td class="text-right">' + asPercentage(primary[key], 'incorrectSetBySystem') + '</td>')
            );
        }
    }, self, sortRev);
    
    $('#pick-stats').html($table);
    globals.lightboxAvatars();
    
    $('.details-link').on('click', function(e) {
        var $this = $(this),
            $tr = $(this).parents('tr:eq(0)'),
            primaryId = $this.attr('primaryid'),
            oldPrimaryDetailId = 0;

        if (teamMode) {
            expanded  = 'team-' + primaryId;
            secondary = primary[primaryId].users;
        } else {
            expanded  = 'user-' + primaryId;
            secondary = primary[primaryId].teams;
        }

        oldPrimaryDetailId = $('.details-row:eq(0)').attr('primaryid');
        $('.details-row').remove();
        if (typeof oldPrimaryDetailId == 'undefined' || oldPrimaryDetailId != primaryId) {
            // we only draw if the new details are for a different parent than the old ones, otherwise we just collapse the details
            globals.bySortedValue(secondary, getSortProp(true), function(key, value) {
                if (secondary[key].total > 0) {
                    $tr.after($('<tr class="details-row" primaryid="' + primaryId + '" />')
                        .append($('<td style="padding-left:20px;" nowrap="nowrap"/>')
                            .append(teamMode ? globals.avatarBubble(usersById[key]) : globals.getTeamLogo(teamsById[key], 'small'))
                        )
                        .append('<td class="text-right">' + secondary[key].total + '</td>')
                        .append('<td class="text-right">' + secondary[key].correct + '</td>')
                        .append('<td class="text-right">' + asPercentage(secondary[key], 'correct') + '</td>')
                        .append('<td class="text-right">' + secondary[key].incorrect + '</td>')
                        .append('<td class="text-right">' + asPercentage(secondary[key], 'incorrect') + '</td>')
                        .append('<td class="text-right">' + secondary[key].manual + '</td>')
                        .append('<td class="text-right">' + asPercentage(secondary[key], 'manual') + '</td>')
                        .append('<td class="text-right">' + secondary[key].setBySystem + '</td>')
                        .append('<td class="text-right">' + asPercentage(secondary[key], 'setBySystem') + '</td>')
                        .append('<td class="text-right">' + secondary[key].correctManual + '</td>')
                        .append('<td class="text-right">' + asPercentage(secondary[key], 'correctManual') + '</td>')
                        .append('<td class="text-right">' + secondary[key].correctSetBySystem + '</td>')
                        .append('<td class="text-right">' + asPercentage(secondary[key], 'correctSetBySystem') + '</td>')
                        .append('<td class="text-right">' + secondary[key].incorrectManual + '</td>')
                        .append('<td class="text-right">' + asPercentage(secondary[key], 'incorrectManual') + '</td>')
                        .append('<td class="text-right">' + secondary[key].incorrectSetBySystem + '</td>')
                        .append('<td class="text-right">' + asPercentage(secondary[key], 'incorrectSetBySystem') + '</td>')
                    );
                }
            }, self, !sortRev);    // note that it needs to be OPPOSITE of the actual sort, because we keep appending rows in reverse order using .after()
        } else {
            expanded = '';
        }
        globals.lightboxAvatars();
        e.preventDefault();
    });

    if ((teamMode && expanded.indexOf('team-') === 0) || (!teamMode && expanded.indexOf('user-') === 0)) {
        $('.details-link[primaryid=' + expanded.replace('team-', '').replace('user-', '') + ']').trigger('click');
    }
    $('#change-mode').html(teamMode ? 'View by User' : 'View by Team');
    $('#change-active').html(showInactive ? 'Exclude Inactive Users' : 'Include Inactive Users');
    $('#change-allmodes').html(showAllModes ? 'Restrict Results to <?php echo (isHardcoreMode() ? 'Hardcore' : 'Normal');?> Mode' : 'Include Picks from All Modes');
    $('#sort-description').html(getSortDescription());
}

function toggleMode() {
    mode = (mode == 'byteam' ? 'byuser' : 'byteam');
    filter();
    draw();
}

function toggleActive() {
    showInactive = !showInactive;
    filter();
    draw();
}

function toggleAllModes() {
    showAllModes = !showAllModes;
    filter();
    draw();
}

$(function() {
    var i;

    for (i=0; i<teams.length; i++) {
        teamsById[teams[i].id] = teams[i];
    }
    for (i=0; i<users.length; i++) {
        usersById[users[i].id] = users[i];
    }

    filter();
    draw();

    $('#change-mode').on('click', function(e) {
        e.preventDefault();
        toggleMode();
    });
    $('#change-active').on('click', function(e) {
        e.preventDefault();
        toggleActive();
    });
    $('#change-allmodes').on('click', function(e) {
        e.preventDefault();
        toggleAllModes();
    });
});
</script>
<div class="container">
    <h2>Pick Statistics</h2>
    <div class="row">
        <div class="col-xs-6 text-left">
            <strong id="sort-description" style="position:relative;bottom:-10px;"></strong>
        </div>
        <div class="col-xs-6 text-right">
            <button id="change-mode" class="btn btn-primary"></button>
            <button id="change-active" class="btn btn-primary"></button>
            <button id="change-allmodes" class="btn btn-primary"></button>
        </div>
    </div>
    <br />
    <div id="pick-stats" style="overflow:auto;"></div>
</div>
