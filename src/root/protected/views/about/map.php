<?php
$isPaid = isPaid();
?>
<div class="container">
    <h2>Site Map</h2>
    <ul>
        <li><strong><?php echo CHtml::link('Home', array('site/index'));?></strong> - The main page.  This page will show the 5 latest talk messages, the current status of the
            <?php echo CHtml::link('Bandwagon', array('about/bandwagon'));?></strong>, the current <?php echo CHtml::link('Payout', array('about/payout'));?> (if the pool were
            to end at this exact moment), and the full Pick Board for the current season.  Note that you can expand/collapse each of these sections by
            clicking on them.</li>
        <li><strong><?php echo CHtml::link('Make Picks', array('pick/index'));?></strong> - This is the place where you can make your pick for the current week, or any future
            weeks in this season</li>
        <li><strong><?php echo $isPaid ? CHtml::link('Messages', array('talk/index')) : 'Messages (Paid Players Only)';?></strong> - On this page, you can post your own talk message, or read any talk messages that have
            been posted in the current season.</li>
        <li><strong>Players/Stats</strong>
            <ul>
                <li><strong><?php echo $isPaid ? CHtml::link('Player Profiles', array('stats/profiles')) : 'Player Profiles (Paid Players Only)';?></strong> - Show a list of all players who've ever played in the Loser Pool.
                    On this page, you can filter users by name or whether or not they're active.  Clicking on an individual user will bring up their personal profile.</li>
                <li><strong><?php echo $isPaid ? CHtml::link('Pick Statistics', array('stats/picks')) : 'Pick Statistics (Paid Players Only)';?></strong> - This complex page shows a table of all picks in the history of the
                    Loser Pool, organized either by Team or by User.  When organized by Team, each Team's detailed view shows all users who've picked that team, and
                    their statistics while doing so.  When organized by User, each User's detailed view shows all teams who that user's picked, and their statistics
                    while doing so.  This page allows you to optionally filter out users who aren't active in the current season.</li>
            </ul>
        </li>
        <li><strong>Archive</strong>
            <ul>
                <li><strong><?php echo $isPaid ? CHtml::link('Previous Winners', array('archive/winners')) : 'Previous Winners (Paid Players Only)';?></strong> - This page shows the entire history of players who've "cashed"
                    (won money) in the Loser Pool.  You may sort this page by any header column, including each individual historical year as a whole, or each pot
                    within a given year.</li>
                <?php
                for ($i=param('earliestYear'); $i<getCurrentYear(); $i++) {
                    ?><li><strong><?php echo $isPaid ? CHtml::link("Past Season: $i", array('archive/year', 'y'=>$i, 'hc'=>0)) : "Past Season $i (Paid Players Only)";?></strong> - The final Pick Board and Talk Messages from <?php echo $i;?></li><?php
                }
                for ($i=param('earliestYearHardcore'); $i<getCurrentYear(); $i++) {
                    ?><li><strong><?php echo $isPaid ? CHtml::link("Past Hardcore Season: $i", array('archive/year', 'y'=>$i, 'hc'=>1)) : "Past Season $i (Paid Players Only)";?></strong> - The final Pick Board and Talk Messages from <?php echo $i;?> (Hardcore)</li><?php
                }
                ?>
            </ul>
        </li>
        <li><strong><?php echo $isPaid ? CHtml::link('Settings', array('profile/index')) : 'Settings (Paid Players Only)';?></strong> - Adjust your personal profile and settings, including the way you view the
            <?php echo Chtml::link("Home Page", array('site/index'));?>.</li>
        <li><strong>About</strong>
            <ul>
                <li><strong><?php echo CHtml::link('Overivew', array('about/overview'));?></strong> - A quick description of the Loser Pool</li>
                <li><strong><?php echo CHtml::link('Site Map', array('about/map'));?></strong> - This page, which gives a brief description of every internal link
                    that's shown in the navigation.</li>
                <li><strong><?php echo CHtml::link('History', array('about/history'));?></strong> - A history of the changes that have been made to the Loser Pool
                    over the course of its existence.</li>
                <li><strong><?php echo CHtml::link('Rules', array('about/rules'));?></strong> - The "how to play" rules of the Loser Pool.</li>
                <li><strong><?php echo CHtml::link('Payout', array('about/payout'));?></strong> - The way the payout is determined each year.</li>
                <li><strong><?php echo CHtml::link('Badges', array('about/badges'));?></strong> - A description of the available badges, how they are earned, who
                    unlocked them, and how many <?php Chtml::link('Power Points', array('about/power'));?> they are worth.</li>
                <li><strong><?php echo CHtml::link('Power Rankings', array('about/power'));?></strong> - What the Power Ranking and Power Points are all about.</li>
                <li><strong><?php echo CHtml::link('Bandwagon', array('about/bandwagon'));?></strong> - What the Bandwagon means, and the badges/statuses related
                    to it.</li>
                <li><strong><?php echo CHtml::link('Technical Info', array('about/tech'));?></strong> - Technical commentary/disclaimer, mostly to chastise you
                    if you use Internet Explorer as your browser.</li>
            </ul>
        </li>
    </ul>
</div>
