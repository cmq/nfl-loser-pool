<?php
// KDHTODO offer a description for each stat somewhere?  Maybe by clicking on the stat?

function drawActiveYears($user) {
    $userYears = array();
    foreach ($user->userYears as $userYear) {
        $userYears[] = $userYear->yr;
    }
    ?>
    <div class="row">
        <div class="col-xs-12 col-md-4">Seasons</div>
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


?>
<script>
$(function() {
    buildTrophyCase(<?php echo CJSON::encode($user);?>, $('#trophycase'));
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
            $seasonStatGroupId = param('id')['statGroup']['season'];
            $picksStatGroupId  = param('id')['statGroup']['picks'];
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
                ?>
                <div class="row">
                    <div class="col-xs-12 col-md-4"><?php echo $userStat->stat->name;?></div>
                    <div class="col-xs-1 text-right"><?php echo formatStat($userStat->value, $userStat->stat->type, $userStat->meta1, $userStat->meta2);?></div>
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
            </div></div>    <!-- end the panel body and panel -->
            
            
            
            <?php
            
            
            // KDHTODO list all the users referred by this user somewhere in their social stats panel
            
            
            
            // KDHTODO put this in the power stats panel even though there are no true "stats" inside that stat group
            
            echo '<hr />';
            $numPowers    = 0;
            $totalPower   = 0;
            $currentPower = null;
            $highestRank   = null;
            $lowestRank    = null;
            foreach ($user->powerRanks as $powerRank) {
                $numPowers++;
                $totalPower += $powerRank['powerrank'];
                if (is_null($highestRank) || $powerRank['powerrank'] < $highestRank) {
                    $highestRank = $powerRank['powerrank'];
                }
                if (is_null($lowestRank) || $powerRank['powerrank'] > $lowestRank) {
                    $lowestRank = $powerRank['powerrank'];
                }
                if ($powerRank['yr'] == getCurrentYear()) {
                    $currentPower  = $powerRank;
                }
            }
            echo 'Highest Power Rank: ' . ($highestRank ? $highestRank : 'N/A') . '<br />';
            echo 'Lowest Power Rank: ' . ($lowestRank ? $lowestRank : 'N/A') . '<br />';
            echo 'Average Power Rank: ' . ($numPowers > 0 ? number_format($totalPower / $numPowers, 1) : 'N/A') . '<br />';
            echo 'Current Power Rank: ' . ($currentPower ? $currentPower['powerrank'] : 'N/A') . '<br />';
            echo 'Current Power Points: ' . ($currentPower ? $currentPower['powerpoints'] : 'N/A') . '<br />';
            if ($currentPower) {
                $multipliers = param('powerMultipliers');
                // KDHTODO break this down into a bar chart with each color representing the percentage
                echo 'Power Point Breakdown:<br />';
                echo 'Points for Seasons Played: ' . $currentPower['seasonPts'] . '<br />';
                echo 'Points for Correct Picks: ' . $currentPower['correctPts'] . '<br />';
                echo 'Points for Badges Held: ' . $currentPower['badgePts'] . '<br />';
                echo 'Points for Money Won: ' . $currentPower['moneyPts'] . '<br />';
                echo 'Points for Win Percentage: ' . $currentPower['winPctPts'] . '<br />';
                echo 'Points for Margin of Defeat: ' . $currentPower['movPts'] . '<br />';
                echo 'Points for Picks Set by System: ' . $currentPower['setBySystemPts'] . '<br />';
                echo 'Points for Messages Posted: ' . $currentPower['talkPts'] . '<br />';
                echo 'Points for Players Referred: ' . $currentPower['referralPts'] . '<br />';
                echo 'Points for "Likes" Given: ' . $currentPower['likesByPts'] . '<br />';
                echo 'Points for "Likes" Received: ' . $currentPower['likesAtPts'] . '<br />';
                echo 'Points for First Place Trophies: ' . $currentPower['firstPlacePts'] . '<br />';
                echo 'Points for Second Place Trophies: ' . $currentPower['secondPlacePts'] . '<br />';
            }
            ?>
            <ul>
                <li>Record per Year (show as bar graph?)</li>
                <li>Number of Times Picking Each Team</li>
                <li>Power-Ranking Calculation Details</li>
                <li>Year-by-year Stat Breakdown?</li>
                <li>Floating or losable badges owned at one point (and which points)</li>
            </ul>
        </div>
    </div>
    <?
} else {
    ?><h4 class="text-danger">That user was not found.</h4><?php
}
?>
</div>