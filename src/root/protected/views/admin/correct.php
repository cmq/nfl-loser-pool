<?php
// KDHTODO normally when this page is submitted, we run power.php and _stats.php -- need to replace those
// KDHTODO clean up the styling, especially of the saved-status fields
$week = isset($_GET['week']) ? (int) $_GET['week'] : getCurrentWeek();
$bShowScores = $week == getCurrentWeek();
if ($bShowScores) {
    $scoresFinal = getLiveScoring();
}
?>


<style>
.saved-status {
    font-weight: bold;
}
.saved-status.error {
    color: red;
}
</style>

<script language="javascript">
$(function() {
    
    // set up a function to count the total mov and to bold those mov rows that do not yet have values
    var fnMov, fnSave, fnStatus, applyingAll=false, saving=false, savePending=false, iSave;

    fnStatus = function(status, err) {
        var saved = typeof status === 'undefined',
            $fields = $('.saved-status');
        $fields.html(saved ? 'Saved' : status);
        if (typeof err === 'undefined') {
            $fields.removeClass('error');
        } else {
            $fields.addClass('error');
        }
    };
    
    fnSave = function() {
        var data = {};
        if (!applyingAll) {
            if (saving) {
                // we're already saving
                if (!savePending) {
                    // try again in 100ms
                    savePending = true;
                    iSave = setInterval(function() {
                        if (!saving) {
                            clearInterval(iSave);
                            savePending = false;
                            fnSave();
                        }
                    }, 100);
                }
            } else {
                saving = true;
                data.incorrect = [];
                data.correct   = [];
                data.notset    = [];
                data.mov       = {};
                $('select.correction').each(function() {
                    var $this  = $(this),
                        val    = $this.val(),
                        userid = $this.attr('userid'),
                        key    = 'notset';
                    if (val == '0') {
                        key = 'correct';
                    } else if (val == '1') {
                        key = 'incorrect';
                    }
                    data[key].push(userid);
                });
                $('input.mov').each(function() {
                    var $this  = $(this),
                        val    = $this.val(),
                        teamid = $this.attr('teamid');
                    data.mov['team' + teamid] = val;
                });
                fnStatus('Saving...');
                $.ajax({
                    url:        '<?php echo Yii::app()->createAbsoluteUrl('admin/correct')?>',
                    data:       {
                                    week: <?php echo $week?>,
                                    data: JSON.stringify(data)
                                },
                    type:       'post',
                    cache:      false,
                    success:    function(response) {
                                    if (response.hasOwnProperty('error') && response.error != '') {
                                        fnStatus(response.error, true);
                                    } else {
                                        fnStatus();
                                    }
                                },
                    error:      function() {
                                    fnStatus('An unknown error occurred.', true);
                                },
                    complete:   function() {
                                    saving = false;
                                },
                    dataType:   'json'
                });
            }
        }
    };
    
    fnMov = function() {
        var netMov = 0;
        $('input.mov').each(function() {
            var mov = parseInt($(this).val(), 10);
            if (isNaN(mov) || mov === 0) {
                $('#team' + $(this).attr('teamid')).addClass('bold');
            } else {
                $('#team' + $(this).attr('teamid')).removeClass('bold');
            }
            if (!isNaN(mov)) {
                netMov += mov;
            }
        });
        $('#netmov').html(netMov);
    };

    // make the "apply mov" buttons set the mov fields
    $('.applymov').click(function(e) {
        var $this = $(this);
        e.preventDefault();
        $('input.mov[teamid=' + $this.attr('teamid') + ']').val($this.attr('mov')).trigger('change');
        fnSave();
    });

    // make the "apply all" button click the individual "apply mov" buttons
    $('.applymovall').click(function(e) {
        e.preventDefault();
        applyingAll = true;
        $('.applymov').trigger('click');
        applyingAll = false;
        fnSave();
    });

    // when a mov field is populated, automatically select correct/incorrect for picks for that team
    $('input.mov').change(function() {
        var $this = $(this),
	    	mov = parseInt($this.val(), 10);
        fnMov();
        $('select.correction[teamid=' + $this.attr('teamid') + ']').val(mov == 0 ? 'null' : (mov < 0 ? 0 : 1));
        fnSave();
    }).keyup(fnMov);
    
    // when one "correction" dropdown changes, change all for the same team
    $('select.correction').on('change', function() {
        var $this = $(this);
        $('select.correction[teamid=' + $this.attr('teamid') + ']').val($this.val());
        fnSave();
    });

    fnMov();
    fnStatus();

});
</script>

<table>
    <tr>
        <th colspan="2" class="saved-status" style="text-align:center;">
        </th>
    </tr>
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
        				<td<?php echo $thisPick && $thisPick->setbysystem ? ' class="setbysystem"' : ''?>><?php echo $thisPick ? $thisPick->team->longname : ''?></td>
        				<td>
        					<select class="correction" userid="<?php echo $user->id?>" teamid="<?php echo $thisPick ? $thisPick->teamid : 0?>">
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
                foreach ($teams as $team) {
                    $thisMov = ($team && count($team->mov) ? $team->mov[0] : null);
                    ?>
                    <tr>
                        <td id="team<?php echo $team->id?>"><?php echo $team->longname?></td>
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
            Net Margin of Victory: <span id="netmov"></span><br />
            <span class="saved-status"></span>
            <br />
            <button>Save</button>
        </td>
    </tr>
</table>
