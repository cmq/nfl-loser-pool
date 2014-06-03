<?php
// KDHTODO re-order the zindex in the stats table, and also group them for display using a new field


?>
<script>
$(function() {
    buildTrophyCase(<?php echo CJSON::encode($user);?>, $('#trophycase'));
});
</script>
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
            echo '<span class="username">#' . $user->power_ranking . ' ' . $user->username . '</span>';
            echo '<div id="trophycase"></div>';
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
                echo $userStat->stat->name . ': ' . formatStat($userStat->value, $userStat->stat->type, $userStat->meta1, $userStat->meta2) . ' (' . ordinal($userStat->place) . ($userStat->tied ? ' (T)' : '') . ' / ' . ordinal($userStat->placeactive) . ($userStat->tiedactive ? ' (T)' : '') . ')<br />';
            }
            
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