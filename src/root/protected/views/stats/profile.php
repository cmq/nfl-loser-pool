<?php
// KDHTODO put power ranking in top-right corner of profile titlebar
// KDHTODO show user badges/trophies in header
// KDHTODO implement all stats
// KDHTODO re-order the zindex in the stats table, and also group them for display using a new field
// KDHTODO make sure badges are calculating correctly in power ranking.  There was a bit of a mess there
// KDHTODO change the lowest/highest/average power ranking to only consider end-of-year rankings.  Week-by-week is kind of pointless without talks/likes/floating badges




// KDHTODO move this method somewhere else
function formatValue($value, $type, $meta1='', $meta2='') {
    $ret = $value;
    switch ($type) {
        case 'money':
            $ret = '$' . number_format((float) $value, 2);
            break;
        case 'int':
            $ret = (int) $value;
            break;
        case 'decimal':
            $ret = number_format((float) $value, 3);
            break;
        case 'percent':
            $ret = number_format((float) $value * 100, 2) . '%';
            break;
    }
    if ($meta1) {
        $ret .= " ($meta1";
        if ($meta2) {
            $ret .= " - $meta2";
        }
        $ret .= ')';
    }
    return $ret;
}
?>
<div class="container">
<?php
if ($user) {
    $userYears = array();
    foreach ($user->userYears as $userYear) {
        $userYears[] = $userYear->yr;
    }
    ?>
    <div class="profile panel panel-primary">
        <div class="panel-heading">
            <?php
            echo getUserAvatar($user);
            echo '<span class="username">' . $user->username . '</span>';
            ?>
        </div>
        <div class="panel-body">
            <?
            if ($user->id == userId()) {
                echo '<p class="text-right">' . CHtml::link('Edit Your Profile Settings', array('profile/index')) . '</p>';
            }
            for ($y=param('earliestYear'); $y<=getCurrentYear(); $y++) {
                echo '<span class="loseryear badge' . (array_search($y, $userYears) === false ? ' inactive' : '') . '">' . $y . '</span>';
            }
            
            
            echo '<hr />';
            foreach ($user->userStats as $userStat) {
                // KDHTODO pull the formatValue() function from the old script so "1" becomes "1st" and so on.  Also so that different stat types get formatted differently (money, etc)
                echo $userStat->stat->name . ': ' . formatValue($userStat->value, $userStat->stat->type, $userStat->meta1, $userStat->meta2) . ' (' . ordinal($userStat->place) . ($userStat->tied ? ' (T)' : '') . ' / ' . ordinal($userStat->placeactive) . ($userStat->tiedactive ? ' (T)' : '') . ')<br />';
            }
            
            echo '<hr />';
            $numPowers    = 0;
            $totalPower   = 0;
            $currentPower = null;
            $lastPowerYear = 0;
            $lastPowerWeek = 0;
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
                if ($powerRank['yr'] > $lastPowerYear || ($powerRank['yr'] == $lastPowerYear && $powerRank['week'] > $lastPowerWeek)) {
                    $currentPower  = $powerRank;
                    $lastPowerYear = $powerRank['yr'];
                    $lastPowerWeek = $powerRank['week'];
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
                echo 'Points for Seasons Played: ' . $currentPower['seasonPts'] . ' (' . ($currentPower['seasonPts']/$multipliers['pointsPerSeason']) . ')<br />';
                echo 'Points for Correct Picks: ' . $currentPower['correctPts'] . ' (' . ($currentPower['correctPts']/$multipliers['pointsPerWin']) . ')<br />';
                // KDHTODO get the number of badges the user has for the area in parens
                echo 'Points for Badges Held: ' . $currentPower['badgePts'] . ' (' . count($user->badges) . ' Badge' . (count($user->badges) != 1 ? 's' : '') . ')<br />';
                echo 'Points for Money Won: ' . $currentPower['moneyPts'] . ' (' . round($currentPower['moneyPts']/$multipliers['pointsPerDollar'], 2) . ')<br />';
                echo 'Points for Win Percentage: ' . $currentPower['winPctPts'] . ' (' . round($currentPower['winPctPts']/$multipliers['winPctMultiplier'], 2) . ')<br />';
                echo 'Points for Margin of Defeat: ' . $currentPower['movPts'] . ' (' . round($currentPower['movPts']/$multipliers['movPoints'], 2) . ')<br />';
                echo 'Points for Picks Set by System: ' . $currentPower['setBySystemPts'] . ' (' . (-1*$currentPower['setBySystemPts']/$multipliers['pointsPerSetBySystem']) . ')<br />';
                echo 'Points for Messages Posted: ' . $currentPower['talkPts'] . ' (' . ($currentPower['talkPts']/$multipliers['pointsPerTalk']) . ')<br />';
                echo 'Points for Players Referred: ' . $currentPower['referralPts'] . ' (' . (($currentPower['referralPts'] * ($user->id == 1 ? 10 : 1))/$multipliers['pointsPerReferral']) . ')<br />';
                echo 'Points for "Likes" Given: ' . $currentPower['likesByPts'] . ' (' . ($currentPower['likesByPts']/$multipliers['pointsPerLikesBy']) . ')<br />';
                echo 'Points for "Likes" Received: ' . $currentPower['likesAtPts'] . ' (' . ($currentPower['likesAtPts']/$multipliers['pointsPerLikesAt']) . ')<br />';
                echo 'Points for First Place Trophies: ' . $currentPower['firstPlacePts'] . ' (' . ($currentPower['firstPlacePts']/$multipliers['pointsPerFirstPlace']) . ')<br />';
                echo 'Points for Second Place Trophies: ' . $currentPower['secondPlacePts'] . ' (' . ($currentPower['secondPlacePts']/$multipliers['pointsPerSecondPlace']) . ')<br />';
            }
            ?>
            <ul>
                <li>Record per Year (show as bar graph?)</li>
                <li>Times Dodging a Bandwagon Crash</li>
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