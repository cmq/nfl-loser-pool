<?php
$totalEntryFee = param('entryFee') + param('movFee');
?>
<div class="container">
    <h2>Payout</h2>
    <p>
        The Loser Pool is split into three pots (two main pots and a smaller additional pot):<br />
        <ul type="1">
            <li><strong>The Stay-Alive Pot (40%)</strong> -- Go the furthest into the season before getting a pick wrong.</li>
            <li><strong>The Best Record Pot (40%)</strong> -- Have the best overall record of correct picks.</li>
            <li><strong>The Biggest Loser Pot (20%)</strong> -- Have the largest combined margin of defeat across all your picks for the season.</li>
        </ul>
        As you can see from the breakdown above, of your $<?php echo number_format($totalEntryFee);?> entry fee:
        <ul>
            <li>$<?php echo number_format($totalEntryFee*.4);?> goes to the Stay-Alive Pot</li>
            <li>$<?php echo number_format($totalEntryFee*.4);?> goes to the Best Record Pot</li>
            <li>$<?php echo number_format($totalEntryFee*.2);?> goes to the Biggest Loser Pot</li>
        </ul>
        From there, the payout is such that in each pot, any players finishing in 1st place will receive twice as much
        money as any players finishing in 2nd place for that pot.  Each pot is calculated separately.
    </p>
    <p>
        As an example, say there are 40 players in the pool for a total pot of $<?php echo number_format($totalEntryFee*40);?>.  This will be
        broken down into $<?php echo number_format(($totalEntryFee*40)*.4);?> for both the Stay-Alive and Best Record pots, and
        $<?php echo number_format(($totalEntryFee*40)*.2);?> for the Biggest Loser pot.
    </p>
    <p>
        Let's say for the Best Record pot there are 4 players who tie for 1st and 2 players who tie for 2nd.  The $<?php echo number_format(($totalEntryFee*40)*.4);?>
        for that pot will be split like:
        <ul>
            <li>Each of the 4 players in 1st will receive $<?php echo number_format(((($totalEntryFee*40)*.4)/10)*2);?></li>
            <li>Each of the 2 players in 2nd will receive $<?php echo number_format((($totalEntryFee*40)*.4)/10);?></li>
        </ul>
    </p>
</div>
