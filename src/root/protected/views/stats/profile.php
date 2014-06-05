<?php
// KDHTODO show floating or losable badges that were owned at one point? (And at which point they were owned)?


function drawActiveYears($user) {
    $userYears = array();
    foreach ($user->userYears as $userYear) {
        $userYears[] = $userYear->yr;
    }
    ?>
    <div class="row">
        <div class="col-xs-12 col-md-4">Seasons (<a title="Seasons" data-content="Shows all seasons of the Loser Pool, with those in which the player has participated highlighted" href="#" class="stat-help-link">?</a>)</div>
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
    <div class="row">
        <div class="col-xs-12 col-md-6">
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
        <div class="col-xs-12 col-md-6">
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
        <div class="col-xs-12 col-md-4">Players Referred (<a title="Players Referred" data-content="Lists all other players that were referred to the Loser Pool by this player" href="#" class="stat-help-link">?</a>)</div>
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
        <div class="col-xs-12 col-md-4">Referred By (<a title="Referred By" data-content="Lists the one player, if known, who referred this player to the Loser Pool" href="#" class="stat-help-link">?</a>)</div>
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
$(function() {
    buildTrophyCase(<?php echo CJSON::encode($user);?>, $('#trophycase'));
    $('.stat-help-link').popover({
        placement: 'auto top'
    }).click(function(e) {
        e.preventDefault();
        $('.stat-help-link').not(this).popover('hide');
        // next line is to fix a bug with the popover plugin (@see https://github.com/twbs/bootstrap/issues/10568)
        $('.popover:not(.in)').hide().detach();
        return false;
    });
});
</script>
<div class="container">
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
            
            
            $lastStatGroup     = 0;
            $currentStreakStatId = param('id')['stat']['currentStreak'];
            $seasonStatGroupId   = param('id')['statGroup']['season'];
            $picksStatGroupId    = param('id')['statGroup']['picks'];
            $socialStatGroupId   = param('id')['statGroup']['social'];
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
                        <!-- KDHTODO how will this heading row work on small devices? -->
                        <div class="row">
                            <div class="col-xs-12 col-md-4"><strong>Stat</strong></div>
                            <div class="col-xs-1 text-right"><strong>Value</strong></div>
                            <div class="col-xs-1 text-right"><strong>Overall</strong></div>
                            <div class="col-xs-1 text-right"><strong>Active</strong></div>
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
                    <div class="col-xs-12 col-md-4"><?php echo $userStat->stat->name;?> (<a title="<?php echo htmlspecialchars($userStat->stat->name)?>" data-content="<?php echo htmlspecialchars($userStat->stat->description)?>" href="#" class="stat-help-link">?</a>)</div>
                    <div class="col-xs-1 text-right"><?php echo $formattedStat;?></div>
                    <div class="col-xs-1 text-right"><?php echo ordinal($userStat->place) . ($userStat->tied ? ' (T)' : '');?></div>
                    <div class="col-xs-1 text-right"><?php echo ordinal($userStat->placeactive) . ($userStat->tiedactive ? ' (T)' : '');?></div>
                    <div class="col-xs-8 col-md-4">
                        <?php
                        if ($userStat->meta1) {
                            echo '(' . $userStat->meta1;
                            if ($userStat->meta2) {
                                echo ' - ' . $userStat->meta2;
                            }
                            echo ')';
                        }
                        ?>
                    </div>
                    <!-- KDHTODO build this as a link to see the full list of users with this stat -->
                    <div class="col-xs-1 text-right"><a href="#">See All</a></div>
                </div>
                <?php
            }
            ?>
            </div></div>    <!-- end the panel body and panel of the last stat group -->
            
            
            <!-- do the power ranking "stat group" which isn't really stats in the normal way, but just collections of details about the power ranking -->
            <?php
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
            ?>
            <div class="panel panel-info">
                <div class="panel-heading">Power Rank Details</div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-xs-12 col-md-4"><strong>Stat</strong></div>
                        <div class="col-xs-1 text-right"><strong>Value</strong></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-md-4">Current Power Rank (<a title="Current Power Rank" data-content="The player's current power rank" href="#" class="stat-help-link">?</a>)</div>
                        <div class="col-xs-1 text-right"><strong><?php echo ($currentPower ? ordinal($currentPower['powerrank']) : 'N/A');?></strong></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-md-4">Current Power Points (<a title="Current Power Points" data-content="The total &quot;score&quot;, or number of points, which is used to calculate the Power Rank of each player" href="#" class="stat-help-link">?</a>)</div>
                        <div class="col-xs-1 text-right"><strong><?php echo ($currentPower ? number_format($currentPower['powerpoints'], 3) : 'N/A');?></strong></div>
                    </div>
                    <?php
                    if ($currentPower) {
                        // KDHTODO break this down into a bar chart with each color representing the percentage
                        ?>
                        <div class="row">
                            <div class="col-xs-11 col-xs-offset-1 col-md-3">Points for Seasons Played (<a title="Points for Seasons Played" data-content="Points earned for each season in which the player participated" href="#" class="stat-help-link">?</a>)</div>
                            <div class="col-xs-1 text-right"><?php echo $currentPower['seasonPts'];?></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-11 col-xs-offset-1 col-md-3">Points for First Place Trophies (<a title="Points for First Place Trophies" data-content="Points earned for finishing in First Place in any of the pots" href="#" class="stat-help-link">?</a>)</div>
                            <div class="col-xs-1 text-right"><?php echo $currentPower['firstPlacePts'];?></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-11 col-xs-offset-1 col-md-3">Points for Second Place Trophies (<a title="Points for Second Place Trophies" data-content="Points earned for finishing in Second Place in any of the pots" href="#" class="stat-help-link">?</a>)</div>
                            <div class="col-xs-1 text-right"><?php echo $currentPower['secondPlacePts'];?></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-11 col-xs-offset-1 col-md-3">Points for Money Won (<a title="Points for Money Won" data-content="Points earned for the amount of cash won by playing" href="#" class="stat-help-link">?</a>)</div>
                            <div class="col-xs-1 text-right"><?php echo $currentPower['moneyPts'];?></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-11 col-xs-offset-1 col-md-3">Points for Correct Picks (<a title="Points for Correct Picks" data-content="Points earned simply for getting picks correct" href="#" class="stat-help-link">?</a>)</div>
                            <div class="col-xs-1 text-right"><?php echo $currentPower['correctPts'];?></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-11 col-xs-offset-1 col-md-3">Points for Win Percentage (<a title="Points for Win Percentage" data-content="Points earned based on winning percentage.  This is modified off a baseline expectation of 50% correct.  This effectivity of this value ramps up over the player's first 50 picks so that the impact of wild swings early in a player's career are dampened." href="#" class="stat-help-link">?</a>)</div>
                            <div class="col-xs-1 text-right"><?php echo $currentPower['winPctPts'];?></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-11 col-xs-offset-1 col-md-3">Points for Margin of Defeat (<a title="Points for Margin of Defeat" data-content="Points earned based on the player's average Margin of Defeat for all picks" href="#" class="stat-help-link">?</a>)</div>
                            <div class="col-xs-1 text-right"><?php echo $currentPower['movPts'];?></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-11 col-xs-offset-1 col-md-3">Points for Picks Set by System (<a title="Points for Picks Set by System" data-content="Points lost when the player fails to make a pick before the lock time, and lets the system pick for them." href="#" class="stat-help-link">?</a>)</div>
                            <div class="col-xs-1 text-right"><?php echo $currentPower['setBySystemPts'];?></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-11 col-xs-offset-1 col-md-3">Points for Messages Posted (<a title="Points for Messages Posted" data-content="Points earned by simply posting chat messages" href="#" class="stat-help-link">?</a>)</div>
                            <div class="col-xs-1 text-right"><?php echo $currentPower['talkPts'];?></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-11 col-xs-offset-1 col-md-3">Points for "Likes" Given (<a title="Points for &quot;Likes&quot; Given" data-content="Points earned by &quot;Liking&quot; other players' messages" href="#" class="stat-help-link">?</a>)</div>
                            <div class="col-xs-1 text-right"><?php echo $currentPower['likesByPts'];?></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-11 col-xs-offset-1 col-md-3">Points for "Likes" Received (<a title="Points for &quot;Likes&quot; Received" data-content="Points earned by having other players &quot;Like&quot; this player's messages" href="#" class="stat-help-link">?</a>)</div>
                            <div class="col-xs-1 text-right"><?php echo $currentPower['likesAtPts'];?></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-11 col-xs-offset-1 col-md-3">Points for Players Referred (<a title="Points for Players Referred" data-content="Points earned by referring other players to the Loser Pool.  NOTE:  Kirk receives referral points at 10% of the normal value due to the large disparity in players referred by the creator of the Loser Pool." href="#" class="stat-help-link">?</a>)</div>
                            <div class="col-xs-1 text-right"><?php echo $currentPower['referralPts'];?></div>
                        </div>
                        <div class="row">
                            <div class="col-xs-11 col-xs-offset-1 col-md-3">Points for Badges Held (<a title="Points for Badges Held" data-content="Points earned for badges the user currently posseses.  Not all badges are worth points, but many are.  See the about page for more information." href="#" class="stat-help-link">?</a>)</div>
                            <div class="col-xs-1 text-right"><?php echo $currentPower['badgePts'];?></div>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="row">
                        <div class="col-xs-12 col-md-4">Highest Power Rank (<a title="Highest Power Rank" data-content="The highest (best) Power Rank ever achieved by the player at the end of any season (or the current spot in the current season)" href="#" class="stat-help-link">?</a>)</div>
                        <div class="col-xs-1 text-right"><?php echo ($highestRank ? ordinal($highestRank) : 'N/A');?></div>
                        <div class="col-xs-1 text-left"><?php echo ($highestRank ? "($highestYear)" : '');?></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-md-4">Lowest Power Rank (<a title="Lowest Power Rank" data-content="The lowest (worst) Power Rank ever achieved by the player at the end of any season (or the current spot in the current season)" href="#" class="stat-help-link">?</a>)</div>
                        <div class="col-xs-1 text-right"><?php echo ($lowestRank ? ordinal($lowestRank) : 'N/A');?></div>
                        <div class="col-xs-1 text-left"><?php echo ($lowestRank ? "($lowestYear)" : '');?></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-md-4">Average Power Rank (<a title="Average Power Rank" data-content="The average Power Rank of the player at the end of all seasons since their first" href="#" class="stat-help-link">?</a>)</div>
                        <div class="col-xs-1 text-right"><?php echo ($numPowers > 0 ? number_format($totalPower / $numPowers, 1) : 'N/A');?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?
} else {
    ?><h4 class="text-danger">That user was not found.</h4><?php
}
?>
</div>