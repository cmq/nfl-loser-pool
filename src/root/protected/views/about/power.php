<?php
$multipliers = param('powerMultipliers');
?>
<div class="container">
    <h2>Power Ranking</h2>
    <p>
        Power Ranking is a feature that was introduced in 2011 to attempt to rank players for how good they were at
        the Loser Pool over their entire career.  The formula has been tweaked a few times to try to come up with a list
        that "feels right."
    </p>
    <p>
        At the end of the day, Power Ranking is about one thing only -- <strong>bragging rights</strong>.  A player's
        Power Ranking does not affect the amount of money they can win.  The only other thing that takes Power Ranking
        into account is the Chief of the Bandwagon Badge, where if there is a tie for Chief, the chief is the tied player
        whose Power Ranking is the highest.
    </p>
    <p>
        Power Ranking is calculated by awarding Power Points to every player based on their actions in the Loser Pool.
        Players are then ordered by the number of Power Points they have and ranked.  Power points can be earned (and LOST)
        in the following ways (subject to change):
        <table class="table table-condensed table-striped table-nonfluid">
            <thead>
                <tr>
                    <th>Action/Factor</th>
                    <th>Power Points</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1st Place Finish</td>
                    <td class="text-right"><?php echo number_format($multipliers['pointsPerFirstPlace'], 2);?></td>
                </tr>
                <tr>
                    <td>2nd Place Finish</td>
                    <td class="text-right"><?php echo number_format($multipliers['pointsPerSecondPlace'], 2);?></td>
                </tr>
                <tr>
                    <td>Season Played</td>
                    <td class="text-right"><?php echo number_format($multipliers['pointsPerSeason'], 2);?></td>
                </tr>
                <tr>
                    <td>Correct Pick</td>
                    <td class="text-right"><?php echo number_format($multipliers['pointsPerWin'], 2);?></td>
                </tr>
                <tr>
                    <td>Every Dollar Won</td>
                    <td class="text-right"><?php echo number_format($multipliers['pointsPerDollar'], 2);?></td>
                </tr>
                <tr>
                    <td>Correct %*</td>
                    <td class="text-right">-<?php echo number_format($multipliers['winPctMultiplier']*(100-$multipliers['winPctThreshold']), 2);?> to +<?php echo number_format($multipliers['winPctMultiplier']*(100-$multipliers['winPctThreshold']), 2);?>*</td>
                </tr>
                <tr>
                    <td>Per Each Point of your Average Marging of Defeat</td>
                    <td class="text-right"><?php echo number_format($multipliers['movPoints'], 2);?></td>
                </tr>
                <tr>
                    <td>Allow a Pick to be Set by System</td>
                    <td class="text-right"><?php echo number_format($multipliers['pointsPerSetBySystem'], 2);?></td>
                </tr>
                <tr>
                    <td>Post a Talk Message</td>
                    <td class="text-right"><?php echo number_format($multipliers['pointsPerTalk'], 2);?></td>
                </tr>
                <tr>
                    <td>Like a Talk Message</td>
                    <td class="text-right"><?php echo number_format($multipliers['pointsPerLikesBy'], 2);?></td>
                </tr>
                <tr>
                    <td>Have Your Talk Message Liked by Someone</td>
                    <td class="text-right"><?php echo number_format($multipliers['pointsPerLikesAt'], 2);?></td>
                </tr>
                <tr>
                    <td>Refer Another Player</td>
                    <td class="text-right"><?php echo number_format($multipliers['pointsPerReferral'], 2);?></td>
                </tr>
            </tbody>
        </table>
        * You may note that the Correct Percentage has a range.  The Correct Percentage takes your lifetime record into account,
        but normalizes it so that new players don't have such wild swings.  How this works is that every player has an expected
        Correct Percentage of <?php echo number_format($multipliers['winPctThreshold'], 1)?>%.  If the player's percentage is
        exactly that, then 0 Power Points will be earned.  If the player has a better record then that, they will gain Power Points.
        If their record is worse, they will LOSE Power Points.  The number of points gained or lost is calculated like this:<br />
        <div class="text-center bold">
            Points = (CorrectPercentage - <?php echo number_format($multipliers['winPctThreshold'], 1)?>%) *
            (min(<?php echo $multipliers['winPctRampUp']?>, max(totalPicksMade, totalPicksMade-<?php echo $multipliers['winPctRampUp']?>))
            / <?php echo $multipliers['winPctRampUp']?>) * <?php echo number_format($multipliers['winPctMultiplier'], 2)?>;
        </div>
        <br />
        This is basically just a fancy way of saying two things:
        <ul type="1">
            <li>Your Correct Percentage minus <?php echo number_format($multipliers['winPctThreshold'], 1)?> times <?php echo number_format($multipliers['winPctMultiplier'], 2)?>
                is how many Power Points you will earn at full magnitude.  So at full magnitude, a perfect Correct Percentage of 100% would earn you:<br />
                (100% - <?php echo number_format($multipliers['winPctThreshold'], 1)?>%) * <?php echo number_format($multipliers['winPctMultiplier'], 2)?> = <strong><?php echo number_format($multipliers['winPctMultiplier']*(100-$multipliers['winPctThreshold']), 2);?> Points</strong>.<br />
                A Correct Percentage, of, say, 78.3% would earn you:<br />
                (78.3% - <?php echo number_format($multipliers['winPctThreshold'], 1)?>%) * <?php echo number_format($multipliers['winPctMultiplier'], 2)?> = <strong><?php echo number_format($multipliers['winPctMultiplier']*(78.3-$multipliers['winPctThreshold']), 2);?> Points</strong>.<br />
            </li>
            <li>
                Your magnitude ramps up at a linear rate until you've reached <?php echo $multipliers['winPctRampUp']?> total lifetime picks.
                In other words, do the calculation above, then multiply by min(1, TotalPicks/<?php echo $multipliers['winPctRampUp']?>).  Once
                you've reached <?php echo $multipliers['winPctRampUp']?> total lifetime picks, you will be at full magnitude forever.
            </li>
        </ul>
    </p>
</div>
