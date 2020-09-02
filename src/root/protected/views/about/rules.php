<?php
$isGuest = isGuest();
$isHardcore = isHardcoreMode();
?>
<div class="container">
    <h2>Rules</h2>
    <p>
        The rules of the Loser Pool are simple:
        <ul type="1">
            <li>Each regular-season and post-season week of the NFL season, you choose one team you think will lose their game that week.</li>
            <li>You must make your selection for the week at least one hour before kickoff of the first game that week.</li>
            <?php
            if (isGuest()) {
                ?>
                <li>In Normal Mode, if you fail to make a selection before the lock time, the team you selected the previous week will be automatically selected for you.  If you fail to make a selection in Week 1, you will automatically be given last year's Superbowl champion.</li>
                <li>In Hardcore Mode, if you fail to make a selection before the lock time, you will have no pick for the week, which will count as a loss (the bad kind).</li>
                <li>In Normal Mode, you may choose the same team to lose as many times as you wish throughout the season.</li>
                <li>In Hardcore Mode, you may only pick each team once for the entire season.</li>
                <?php
            } else {
                if (isHardcoreMode()) {
                    ?>
                    <li>Since this is hardcore mode, you may only pick each team <strong>once for the entire season.</strong></li>
                    <?php
                } else {
                    ?>
                    <li>If you fail to make a selection before the lock time, the team you selected the previous week will be automatically selected for you.  If you fail to make a selection in Week 1, you will automatically be given last year's Superbowl champion.</li>
                    <li>You may choose the same team to lose as many times as you wish throughout the season.</li>
                    <?php
                }
            }
            ?>
            <li>If your team loses, you get a "win" (the good kind).  If your team wins or ties or doesn't play, you don't.</li>
        </ul>
    </p>
</div>
