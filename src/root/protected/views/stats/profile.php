<?php
// KDHTODO put power ranking in top-right corner of profile titlebar
// KDHTODO show user badges/trophies in header
// KDHTODO implement all stats
// KDHTODO re-order the zindex in the stats table, and also group them for display using a new field



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
                echo $userStat->stat->name . ': ' . formatValue($userStat->value, $userStat->stat->type, $userStat->meta1, $userStat->meta2) . ' (' . $userStat->place . ($userStat->tied ? 'T' : '') . ' / ' . $userStat->placeactive . ($userStat->tiedactive ? 'T' : '') . ')<br />';
            }
            
            ?>
            <ul>
                <li>Record per Year (show as bar graph?)</li>
                <li>Times Dodging a Bandwagon Crash</li>
                <li>Number of Times Picking Each Team</li>
                <li>Current Power Ranking</li>
                <li>Highest Power Ranking</li>
                <li>Lowest Power Ranking</li>
                <li>Largest One-Week Power Ranking Jump</li>
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