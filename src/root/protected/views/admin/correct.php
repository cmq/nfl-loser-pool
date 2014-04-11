<?php

// KDHTODO normally when this page is submitted, we run power.php and _stats.php -- need to replace those
// KDHTODO handle javascript interactions
// KDHTODO handle POSTing

$week = isset($_GET['week']) ? (int) $_GET['week'] : getCurrentWeek();
$bShowScores = $week == getCurrentWeek();
if ($bShowScores) {
    $scoresFinal = getLiveScoring();
}
?>

<table>
    <tr>
        <th>User Picks</th>
        <th>Margin of Victory</th>
    </tr>
    <tr>
        <td valign="top">
        	<table>
        		<tr>
        			<th>User</th>
        			<th>Pick</th>
        			<th>Incorrect?</th>
        		</tr>
        		<?
        		$nNumPicks = 0;
        		$nNumPlayers = 0;
        		foreach ($users as $user) {
                    $thisPick  = ($user->picks && count($user->picks) && $user->picks[0]->team ? $user->picks[0] : null);
                    $incorrect = $thisPick ? $thisPick->incorrect : null;
        			$nNumPicks += $thisPick ? 1 : 0;
        			$nNumPlayers++;
        			?>
        			<tr>
        				<td><?php echo $user->username?></td>
        				<td><?php echo $thisPick ? $thisPick->team->longname : ''?></td>    <!-- KDHTODO show in blue if this pick was set by system -->
        				<td>
        					<select class="incorrect" userid="<?php echo $user->id?>" teamid="<?php echo $thisPick ? $thisPick->teamid : 0?>">
        						<option value="null"<?php echo (is_null($incorrect) ? " selected=\"selected\"" : "")?>>-</option>
        						<option value="0"<?php echo (!is_null($incorrect) && (int) $incorrect === 0 ? " selected=\"selected\"" : "")?>>Correct</option>
        						<option value="1"<?php echo (!is_null($incorrect) && (int) $incorrect === 1 ? " selected=\"selected\"" : "")?>>Incorrect</option>
        					</select>
        				</td>
        			</tr>
        			<?
        		}
        		?>
        	</table>
        </td>
        <td valign="top">
            <table border="0" cellspacing="1" cellpadding="2">
                <tr class="tableHeads">
                    <th>Team</th>
                    <th>MOV</th>
		            <?php echo ($bShowScores ? '<td>Score</td><td><button class="applymovall">Apply All</button></td>' : ''); ?>
                </tr>
                <?
                $netMOV = 0;
                foreach ($teams as $team) {
                    $thisMov = ($team && count($team->mov) ? $team->mov[0] : null);
                    $netMOV  += $thisMov ? $thisMov->mov : 0;
                    ?>
                    <tr>
                        <td><?php echo $team->longname?></td>
                        <td>
                            <input class="mov" teamid="<?php echo $team->id?>" value="<?php echo $thisMov ? $thisMov->mov : 0?>" />
                        </td>
                        <?php 
                        if ($bShowScores) {
                            $thisGame = null;
                            foreach ($scoresFinal as $score) {
                                if ($score['awayteam'] == $team->shortname) {
                                    $thisGame = $score;
                                    $isFinal  = $score['final'];
                                    $awayTeam = true;
                                    $mov      = $score['awaymov'];
                                    break;
                                }
                                if ($score['hometeam'] == $team->shortname) {
                                    $thisGame = $score;
                                    $isFinal  = $score['final'];
                                    $awayTeam = false;
                                    $mov      = $score['homemov'];
                                    break;
                                }
                            }
                            if ($thisGame) {
                                // KDHTODO we may not have the "bold" class, so may need to style this differently
                                echo '<td>';
                                echo '<span class="' . ($awayTeam ? 'bold' : '') . '">' . $thisGame['awayteam'] . ' ' . $thisGame['awayscore'] . '</span>, ';
                                echo '<span class="' . (!$awayTeam ? 'bold' : '') . '">' . $thisGame['hometeam'] . ' ' . $thisGame['homescore'] . '</span> ';
                                echo '</td><td>';
                                if ($isFinal) {
                                    echo '<button class="applymov" mov="' . $mov . '" teamid="' . $team->id . '">Apply ' . $mov . '</button>';
                                }
                                echo '</td>';
                            } else {
                                echo '<td>&nbsp;</td><td>&nbsp;</td>';
                            }
                        }
                        ?>
                    </tr>
                    <?
                }
                ?>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2" align="center">
            <br />
            <?=$nNumPicks?> / <?=$nNumPlayers?> picks made.<br />
            Net Margin of Victory: <span id="netmov"><?=$netMOV;?></span><br />
            <br />
            <button>Save</button>
        </td>
    </tr>
</table>
