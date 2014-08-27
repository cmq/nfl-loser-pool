<script src="<?php echo baseUrl('/js/lib/highcharts.js'); ?>"></script>
<?php

// do the power ranking "stat group" which isn't really stats in the normal way, but just collections of details about the power ranking
$numPowers    = 0;
$totalPower   = 0;
$currentPower = null;
$highestRank  = null;
$lowestRank   = null;
$highestYear  = '';
$lowestYear   = '';
foreach ($user->powerRanks as $powerRank) {
    $numPowers++;
    $totalPower += $powerRank['powerrank'];
    if (is_null($highestRank) || $powerRank['powerrank'] < $highestRank) {
        $highestRank = $powerRank['powerrank'];
        $highestYear = $powerRank['yr'];
    }
    if (is_null($lowestRank) || $powerRank['powerrank'] > $lowestRank) {
        $lowestRank = $powerRank['powerrank'];
        $lowestYear = $powerRank['yr'];
    }
    if ($powerRank['yr'] == getCurrentYear()) {
        $currentPower  = $powerRank;
    }
}


function drawActiveYears($user) {
    $userYears = array();
    foreach ($user->userYears as $userYear) {
        $userYears[] = $userYear->yr;
    }
    ?>
    <div class="row">
        <div class="col-xs-12 col-md-4">Seasons (<a title="Seasons" data-content="Shows all seasons of the Loser Pool, with those in which the player has participated highlighted" href="#" class="stat-help-link spawns-popover">?</a>)</div>
        <div class="col-xs-12 col-md-8">
            <?php
            for ($y=param('earliestYear'); $y<=getCurrentYear(); $y++) {
                echo '<span class="loseryear badge' . (array_search($y, $userYears) === false ? ' inactive' : '') . '">' . $y . '</span>';
            }
            ?>
        </div>
    </div>
    <?php
}

function drawPieChartDataPiece($title, $stat, $isFirst=false) {
    $title = addslashes($title);
    if ($stat != 0) {
        if ($stat < 0) {
            $stat  = abs($stat);
            return ($isFirst ? '' : ', ') . "{name: '$title', y: $stat, color: '#f6f6f6'}";
        } else {
            return ($isFirst ? '' : ', ') . "['$title', $stat]";
        }
    }
    return '';
}

function drawPickStatsTableStat($stats, $key) {
    $stat = $stats[$key];
    return '<td class="text-right">' . formatStat($stat['value'], $stat['type']) . '</td><td class="text-right">' . ordinal($stat['place']) . ($stat['tied'] ? ' (T)' : '') . '</td>';
}

function drawPickStatsTables($user) {
    $stats = array();
    foreach ($user->userStats as $userStat) {
        $stats[$userStat->stat->id] = array(
            'type'        => $userStat->stat->type,
            'value'       => $userStat->value,
            'place'       => $userStat->place,
            'placeactive' => $userStat->placeactive,
            'tied'        => $userStat->tied,
            'tiedActive'  => $userStat->tiedactive,
        );
    }
    $ids = param('id');
    ?>
    <br />
    <div class="row hidden-xs">
        <div class="col-xs-12 col-md-7">
            <strong>Picks by Number</strong>
            <table class="table table-condensed table-bordered" style="border:none;">
                <tr>
                    <td style="border-top:none;border-left:none;">&nbsp;</td>
                    <td class="text-center" colspan="2">Manual</td>
                    <td class="text-center" colspan="2">Set By System</td>
                    <td class="text-center" colspan="2">Total</td>
                </tr>
                <tr>
                    <td>Correct</td>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['picksCorrectManual']);?>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['picksCorrectSetBySystem']);?>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['picksCorrect']);?>
                </tr>
                <tr>
                    <td>Incorrect</td>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['picksIncorrectManual']);?>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['picksIncorrectSetBySystem']);?>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['picksIncorrect']);?>
                </tr>
                <tr>
                    <td>Total</td>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['picksManual']);?>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['picksSetBySystem']);?>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['picksTotal']);?>
                </tr>
            </table>
        </div>
    </div>
    <div class="row hidden-xs">
        <div class="col-xs-12 col-md-7">
            <strong>Picks by Percentage</strong>
            <table class="table table-condensed table-bordered" style="border:none;">
                <tr>
                    <td style="border-top:none;border-left:none;">&nbsp;</td>
                    <td class="text-center" colspan="2">Manual</td>
                    <td class="text-center" colspan="2">Set By System</td>
                    <td class="text-center" colspan="2">Total</td>
                </tr>
                <tr>
                    <td>Correct</td>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['rateCorrectManual']);?>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['rateCorrectSetBySystem']);?>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['rateCorrect']);?>
                </tr>
                <tr>
                    <td>Incorrect</td>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['rateIncorrectManual']);?>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['rateIncorrectSetBySystem']);?>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['rateIncorrect']);?>
                </tr>
                <tr>
                    <td>Total</td>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['rateManual']);?>
                    <?php echo drawPickStatsTableStat($stats, $ids['stat']['rateSetBySystem']);?>
                    <td class="text-right">100.00%</td>
                    <td class="text-right">N/A</td> 
                </tr>
            </table>
        </div>
    </div>
    <?php
}

function drawReferrals($user) {
    ?>
    <div class="row">
        <div class="col-xs-12 col-md-4">Players Referred (<a title="Players Referred" data-content="Lists all other players that were referred to the Loser Pool by this player" href="#" class="stat-help-link spawns-popover">?</a>)</div>
        <div class="col-xs-12 col-md-8">
            <?php
            if ($user->referrals) {
                foreach ($user->referrals as $idx=>$referral) {
                    echo '<div class="referral-display">';
                    echo getAvatarProfileLink($referral, true, true);
                    echo '</div>';
                }
            } else {
                echo 'None';
            }
            ?>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-md-4">Referred By (<a title="Referred By" data-content="Lists the one player, if known, who referred this player to the Loser Pool" href="#" class="stat-help-link spawns-popover">?</a>)</div>
        <div class="col-xs-12 col-md-8">
            <?php
            if ($user->referredBy) {
                echo '<div class="referral-display parent">';
                echo getAvatarProfileLink($user->referredBy, true);
                echo '</div>';
            } else {
                echo 'Unknown';
            }
            ?>
        </div>
    </div>
    <?php
}




?>
<script>
var modals = [];
$(function() {

    document.title = 'Player Profile: #<?php echo addslashes($user->power_ranking . ' ' . $user->username);?>';
    
    buildTrophyCase(<?php echo CJSON::encode($user);?>, $('#trophycase'));
    
    $('.stat-help-link').popover({
        placement: 'auto top'
    }).click(function(e) {
        e.preventDefault();
        $('.spawns-popover').not(this).popover('hide');
        // next line is to fix a bug with the popover plugin (@see https://github.com/twbs/bootstrap/issues/10568)
        $('.popover:not(.in)').hide().detach();
        return false;
    });

    $('.see-all-link').click(function(e) {
        var statid = $(this).data('statid');
        e.preventDefault();
        if (modals.hasOwnProperty(statid)) {
            modals[statid].modal('show');
        }
        return false;
    });

    // set up a modal to show the full user stat list for each stat
    <?php
    $lastStatId   = 0;
    $lastStat     = null;
    $tablePattern = '<div class="stat-description">%s</div><table class="table table-striped table-condensed"><thead><tr><th class="text-right">Place</th><th class="text-center">User</th><th class="text-right">Value</th></tr></thead><tbody>%s</tbody></table>';
    foreach ($allStats as $userStat) {
        if ($userStat->statid != $lastStatId) {
            if ($lastStatId) {
                ?>
                modals[<?php echo $lastStatId?>] = globals.getModal('stat-full-<?php echo $lastStatId;?>', '<?php echo addslashes($lastStat->name);?>', '<?php echo addslashes(sprintf($tablePattern, $lastStat->description, $tableRows));?>');
                <?php
            }
            $lastStatId = $userStat->statid;
            $lastStat   = $userStat->stat;
            $tableRows  = '';
        }
        $tableRows .= '<tr' . ($userStat->user->id == userId() ? ' class="success"' : '') . '><td class="text-right">' . ordinal($userStat->place) . '</td><td class="text-center">' . ($userStat->user->active ? '<strong>' . getProfileLink($userStat->user) . '</strong>' : getProfileLink($userStat->user)) . '</td><td class="text-right">' . formatStat($userStat->value, $userStat->stat->type) . '</td></tr>';
    }
    if ($lastStatId) {
        ?>
        modals.push(globals.getModal('stat-full-<?php echo $lastStatId;?>', '<?php echo addslashes($lastStat->name);?>', '<?php echo addslashes(sprintf($tablePattern, $lastStat->description, $tableRows));?>'));
        <?php
    }
    ?>
    for (var i in modals) {
        if (modals.hasOwnProperty(i)) {
            modals[i].modal({
                show: false
            });
        }
    }

    <?php
    // set up the pie chart
    if ($currentPower) {
        ?>
        $('#power-breakdown').highcharts({
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false
            },
            title: {
                text: 'Power Point Sources'
            },
            tooltip: {
        	    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                        style: {
                            color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                        }
                    }
                }
            },
            series: [{
                type: 'pie',
                name: 'Point Source',
                data: [
                    <?php echo drawPieChartDataPiece('Seasons Played', $currentPower['seasonPts'], true);?>
                    <?php echo drawPieChartDataPiece('1st Place Finishes', $currentPower['firstPlacePts']);?>
                    <?php echo drawPieChartDataPiece('2nd Place Finishes', $currentPower['secondPlacePts']);?>
                    <?php echo drawPieChartDataPiece('Money Won', $currentPower['moneyPts']);?>
                    <?php echo drawPieChartDataPiece('Correct Picks', $currentPower['correctPts']);?>
                    <?php echo drawPieChartDataPiece('Win Percentage', $currentPower['winPctPts']);?>
                    <?php echo drawPieChartDataPiece('Margin of Defeat', $currentPower['movPts']);?>
                    <?php echo drawPieChartDataPiece('Messages Posted', $currentPower['talkPts']);?>
                    <?php echo drawPieChartDataPiece('"Likes" Given', $currentPower['likesByPts']);?>
                    <?php echo drawPieChartDataPiece('"Likes" Received', $currentPower['likesAtPts']);?>
                    <?php echo drawPieChartDataPiece('Players Referred', $currentPower['referralPts']);?>
                    <?php echo drawPieChartDataPiece('Badges Held', $currentPower['badgePts']);?>
                    <?php echo drawPieChartDataPiece('Set by System (forfeited)', $currentPower['setBySystemPts']);?>
                ]
            }]
        });        
        <?php
    }
    ?>
    
});
</script>
<div class="container">
<?php
echo CHtml::link('&laquo; Back to Profiles List', array('stats/profiles'));
?>

<h2>User Profile</h2>

<?php

if ($user) {
    ?>
    <div class="profile panel panel-primary">
        <div class="panel-heading">
            <?php
            echo getUserAvatar($user);
            echo '<span class="username">#' . $user->power_ranking . ' ' . $user->username . '</span>';
            echo '<div id="trophycase"></div>';
            ?>
        </div>
        <div class="panel-body">
        
            <?php
            if ($user->id == userId()) {
                echo '<p class="text-right">' . CHtml::link('Edit Your Profile Settings', array('profile/index')) . '</p>';
            }
            
            if (count($user->userStats)) {
                $lastStatGroup       = 0;
                $ids                 = param('id');
                $currentStreakStatId = $ids['stat']['currentStreak'];
                $seasonStatGroupId   = $ids['statGroup']['season'];
                $picksStatGroupId    = $ids['statGroup']['picks'];
                $socialStatGroupId   = $ids['statGroup']['social'];
                foreach ($user->userStats as $userStat) {
                    if ($lastStatGroup != $userStat->stat->statGroup->id) {
                        if ($lastStatGroup) {
                            // special case -- show the active years
                            if ($lastStatGroup == $seasonStatGroupId) {
                                drawActiveYears($user);
                            }
                            // special case -- show the pick stats as a table
                            if ($lastStatGroup == $picksStatGroupId) {
                                drawPickStatsTables($user);
                            }
                            // special case -- show users referred
                            if ($lastStatGroup == $socialStatGroupId) {
                                drawReferrals($user);
                            }
                            ?>
                            </div></div>    <!-- end the panel body and panel -->
                            <?php
                        }
                        ?>
                        <div class="panel panel-info">
                            <div class="panel-heading"><?php echo $userStat->stat->statGroup->name?></div>
                            <div class="panel-body">
                            <div class="row">
                                <div class="col-xs-4 col-md-4"><strong>Stat</strong></div>
                                <div class="hidden-xs col-md-1 text-right"><strong>Value</strong></div>
                                <div class="col-xs-4 col-md-1 text-right"><strong>Overall</strong></div>
                                <div class="col-xs-4 col-md-1 text-right"><strong>Active</strong></div>
                            </div>
                        <?php
                        $lastStatGroup = $userStat->stat->statGroup->id;
                    }
                    $formattedStat = formatStat($userStat->value, $userStat->stat->type);
                    /*
                    // commenting this out for now, because it causes wrapping issues
                    if ($userStat->stat->id == $currentStreakStatId) {
                        $formattedStat = 'N/A';
                        if ($userStat->value < 0) {
                            $formattedStat = abs($userStat->value) . ' Incorrect';
                        } else if ($userStat->value > 0) {
                            $formattedStat = $userStat->value . ' Correct';
                        }
                    }
                    */
                    ?>
                    <div class="row">
                        <div class="col-xs-12 col-md-4"><a href="#" class="see-all-link" data-statid="<?php echo $userStat->statid;?>"><?php echo $userStat->stat->name;?></a> (<a title="<?php echo htmlspecialchars($userStat->stat->name)?>" data-content="<?php echo htmlspecialchars($userStat->stat->description)?>" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                        <div class="col-xs-4 col-md-1 text-right"><?php echo $formattedStat;?></div>
                        <div class="col-xs-4 col-md-1 text-right"><?php echo ordinal($userStat->place) . ($userStat->tied ? ' (T)' : '');?></div>
                        <div class="col-xs-4 col-md-1 text-right"><?php echo $user->active ? (ordinal($userStat->placeactive) . ($userStat->tiedactive ? ' (T)' : '')) : '-';?></div>
                        <?php
                        if ($userStat->meta1) {
                            echo '<div class="col-xs-12 col-md-4">(' . $userStat->meta1;
                            if ($userStat->meta2) {
                                echo ' - ' . $userStat->meta2;
                            }
                            echo ')</div>';
                        }
                        ?>
                    </div>
                    <?php
                }
                ?>
                </div></div>    <!-- end the panel body and panel of the last stat group -->
                
                
                <div class="panel panel-info">
                    <div class="panel-heading">Power Rank Details</div>
                    <div class="panel-body">
                    
                        <div class="row">
                            <div class="col-xs-12 col-md-6" style="float:right;">
                                <div id="power-breakdown"></div>
                            </div>
                            <div class="col-xs-12 col-md-4"><strong>Stat</strong></div>
                            <div class="col-md-1 hidden-xs text-right"><strong>Value</strong></div>
                            <div class="col-xs-8 col-md-4">Current Power Rank (<a title="Current Power Rank" data-content="The player's current power rank" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                            <div class="col-xs-4 col-md-1 col-md-1 text-right"><strong><?php echo ($currentPower ? ordinal($currentPower['powerrank']) : 'N/A');?></strong></div>
                            <div class="col-xs-8 col-md-4">Current Power Points (<a title="Current Power Points" data-content="The total &quot;score&quot;, or number of points, which is used to calculate the Power Rank of each player" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                            <div class="col-xs-4 col-md-1 text-right"><strong><?php echo ($currentPower ? number_format($currentPower['powerpoints'], 3) : 'N/A');?></strong></div>
                            <?php
                            if ($currentPower) {
                                ?>
                                <div class="col-xs-7 col-xs-offset-1 col-md-3">Seasons Played (<a title="Points for Seasons Played" data-content="Points earned for each season in which the player participated" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                                <div class="col-xs-4 col-md-1 text-right"><?php echo $currentPower['seasonPts'];?></div>
                                <div class="col-xs-7 col-xs-offset-1 col-md-3">1st Place Trophies (<a title="Points for First Place Trophies" data-content="Points earned for finishing in First Place in any of the pots" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                                <div class="col-xs-4 col-md-1 text-right"><?php echo $currentPower['firstPlacePts'];?></div>
                                <div class="col-xs-7 col-xs-offset-1 col-md-3">2nd Place Trophies (<a title="Points for Second Place Trophies" data-content="Points earned for finishing in Second Place in any of the pots" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                                <div class="col-xs-4 col-md-1 text-right"><?php echo $currentPower['secondPlacePts'];?></div>
                                <div class="col-xs-7 col-xs-offset-1 col-md-3">Money Won (<a title="Points for Money Won" data-content="Points earned for the amount of cash won by playing" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                                <div class="col-xs-4 col-md-1 text-right"><?php echo $currentPower['moneyPts'];?></div>
                                <div class="col-xs-7 col-xs-offset-1 col-md-3">Correct Picks (<a title="Points for Correct Picks" data-content="Points earned simply for getting picks correct" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                                <div class="col-xs-4 col-md-1 text-right"><?php echo $currentPower['correctPts'];?></div>
                                <div class="col-xs-7 col-xs-offset-1 col-md-3">Win Percentage (<a title="Points for Win Percentage" data-content="Points earned based on winning percentage.  This is modified off a baseline expectation of 50% correct.  This effectivity of this value ramps up over the player's first 50 picks so that the impact of wild swings early in a player's career are dampened." href="#" class="stat-help-link spawns-popover">?</a>)</div>
                                <div class="col-xs-4 col-md-1 text-right"><?php echo $currentPower['winPctPts'];?></div>
                                <div class="col-xs-7 col-xs-offset-1 col-md-3">Margin of Defeat (<a title="Points for Margin of Defeat" data-content="Points earned based on the player's average Margin of Defeat for all picks" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                                <div class="col-xs-4 col-md-1 text-right"><?php echo $currentPower['movPts'];?></div>
                                <div class="col-xs-7 col-xs-offset-1 col-md-3">Set by System (<a title="Points for Picks Set by System" data-content="Points lost when the player fails to make a pick before the lock time, and lets the system pick for them." href="#" class="stat-help-link spawns-popover">?</a>)</div>
                                <div class="col-xs-4 col-md-1 text-right"><?php echo $currentPower['setBySystemPts'];?></div>
                                <div class="col-xs-7 col-xs-offset-1 col-md-3">Messages Posted (<a title="Points for Messages Posted" data-content="Points earned by simply posting chat messages" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                                <div class="col-xs-4 col-md-1 text-right"><?php echo $currentPower['talkPts'];?></div>
                                <div class="col-xs-7 col-xs-offset-1 col-md-3">"Likes" Given (<a title="Points for &quot;Likes&quot; Given" data-content="Points earned by &quot;Liking&quot; other players' messages" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                                <div class="col-xs-4 col-md-1 text-right"><?php echo $currentPower['likesByPts'];?></div>
                                <div class="col-xs-7 col-xs-offset-1 col-md-3">"Likes" Received (<a title="Points for &quot;Likes&quot; Received" data-content="Points earned by having other players &quot;Like&quot; this player's messages" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                                <div class="col-xs-4 col-md-1 text-right"><?php echo $currentPower['likesAtPts'];?></div>
                                <div class="col-xs-7 col-xs-offset-1 col-md-3">Players Referred (<a title="Points for Players Referred" data-content="Points earned by referring other players to the Loser Pool.  NOTE:  Kirk receives referral points at 10% of the normal value due to the large disparity in players referred by the creator of the Loser Pool." href="#" class="stat-help-link spawns-popover">?</a>)</div>
                                <div class="col-xs-4 col-md-1 text-right"><?php echo $currentPower['referralPts'];?></div>
                                <div class="col-xs-7 col-xs-offset-1 col-md-3">Badges Held (<a title="Points for Badges Held" data-content="Points earned for badges the user currently posseses.  Not all badges are worth points, but many are.  See the about page for more information." href="#" class="stat-help-link spawns-popover">?</a>)</div>
                                <div class="col-xs-4 col-md-1 text-right"><?php echo $currentPower['badgePts'];?></div>
                                <?php
                            }
                            ?>
                            <div class="col-xs-8 col-md-4">Highest Power Rank (<a title="Highest Power Rank" data-content="The highest (best) Power Rank ever achieved by the player at the end of any season (or the current spot in the current season)" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                            <div class="hidden-xs col-md-1 text-right"><?php echo ($highestRank ? ordinal($highestRank) : 'N/A');?></div>
                            <div class="hidden-xs col-md-1 text-left"><?php echo ($highestRank ? "($highestYear)" : '');?></div>
                            <div class="hidden-md hidden-sm hidden-lg col-xs-4 text-right"><?php echo ($highestRank ? ordinal($highestRank) : 'N/A') . ($highestRank ? " ($highestYear)" : '');?></div>
                            <div class="col-xs-8 col-md-4">Lowest Power Rank (<a title="Lowest Power Rank" data-content="The lowest (worst) Power Rank ever achieved by the player at the end of any season (or the current spot in the current season)" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                            <div class="hidden-xs col-md-1 text-right"><?php echo ($lowestRank ? ordinal($lowestRank) : 'N/A');?></div>
                            <div class="hidden-xs col-md-1 text-left"><?php echo ($lowestRank ? "($lowestYear)" : '');?></div>
                            <div class="hidden-md hidden-sm hidden-lg col-xs-4 text-right"><?php echo ($lowestRank ? ordinal($lowestRank) : 'N/A') . ($lowestRank ? " ($lowestYear)" : '');?></div>
                            <div class="col-xs-8 col-md-4">Average Power Rank (<a title="Average Power Rank" data-content="The average Power Rank of the player at the end of all seasons since their first" href="#" class="stat-help-link spawns-popover">?</a>)</div>
                            <div class="col-xs-4 col-md-1 text-right"><?php echo ($numPowers > 0 ? number_format($totalPower / $numPowers, 1) : 'N/A');?></div>
                        </div>
                            
                    </div>
                </div>
                <?php
            } else {
                echo $user->username . ' is a new player.  Profile will be available after Week 1 is locked.';
            }
            ?>
        </div>
    </div>
    <?
} else {
    ?><h4 class="text-danger">That user was not found.</h4><?php
}
?>
</div>