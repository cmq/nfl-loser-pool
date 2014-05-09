<?php
// KDHTODO put power ranking in top-right corner of profile titlebar
// KDHTODO show user badges/trophies in header
// KDHTODO implement all stats
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
            ?>
            <ul>
                <li>Years played</li>
                <li>Trophies/Badges</li>
                <li>Record per Year (show as bar graph?)</li>
                <li>Seasons Played (count)</li>
                <li>Entry Fees Paid</li>
                <li>Money Earned</li>
                <li>ROI</li>
                <li>Total Picks and Percentage Made for:
                    <ul>
                        <li>Total (this won't have a percentage (well, it'll always be 100%))</li>
                        <li>Correct</li>
                        <li>Incorrect</li>
                        <li>Manual</li>
                        <li>Set by System</li>
                        <li>Correct/Manual</li>
                        <li>Correct/Set by System</li>
                        <li>Incorrect/Manual</li>
                        <li>Incorrect/Set by System</li>
                    </ul>
                </li>
                <li>Power Ranking</li>
                <li>Total MOV</li>
                <li>Average MOV</li>
                <li>Longest Correct Streak</li>
                <li>Longest Incorrect Streak</li>
                <li>Average Correct per Season</li>
                <li>Average Incorrect per Season</li>
                <li>Average Week of First Incorrect Pick</li>
                <li>Messages Posted</li>
                <li>Likes Given</li>
                <li>Likes Received</li>
                <li>Average Time Pick Made Before Lock</li>
                <li>Times on Bandwagon</li>
                <li>Times Off Bandwagon</li>
                <li>Num Players Referred</li>
                <li>Num 1st Place Finishes</li>
                <li>Num 2nd Place Finishes</li>
                <li>Num Trophies</li>
                <li>Num Badges</li>
                <li>Times Dodging a Bandwagon Crash</li>
                <li>Times Being Bandwagon Chief</li>
                <li>Number of Times Picking Each Team</li>
                <li>Current Power Ranking</li>
                <li>Highest Power Ranking</li>
                <li>Lowest Power Ranking</li>
                <li>Largest One-Week Power Ranking Jump</li>
                <li>Power-Ranking Calculation Details</li>
            </ul>
        </div>
    </div>
    <?
} else {
    ?><h4 class="text-danger">That user was not found.</h4><?php
}
?>
</div>