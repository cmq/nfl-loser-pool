<?php
$delayAmount = (!isPaid() ? getCurrentWeek() * 10 : 0);
$pickByWeek = array();
for ($i=1; $i<=getMaxWeeks(); $i++) {
    $pickByWeek[$i] = null;
}
foreach ($picks as $pick) {
    $pickByWeek[$pick['week']] = $pick;
}
?>
<script>
var picksByWeek = Array(globals.getMaxWeeks()+1);   // 0-based array, 0 will be empty, 1-21 (now 22) will be the weeks
<?php
foreach ($picks as $pick) {
    echo "picksByWeek[" . $pick['week'] . "] = " . (int) $pick['teamid'] . ";\n";
}
?>

function hardcoreAdjust() {
    <?php
    if (!isHardcoreMode()) {
        echo 'return false;';
    } else {
        ?>
        $('.team-pick').each(function() {
            var $select = $(this),
                week    = parseInt($select.data('week'), 10);
            $('option', $select).each(function() {
                var $option = $(this),
                    teamid  = parseInt($option.val(), 10),
                    teamok  = true;
                if (!isNaN(teamid)) {
                    if (picksByWeek.indexOf(teamid) >= 1) {
                        // this team has been picked before...
                        if (picksByWeek[week] != teamid) {
                            // the team was picked for a different week other than the one we're looking at.  We need to disable it on this week
                            teamok = false;
                        }
                    }
                }
                $option.prop('disabled', (teamok ? false : true));
            });
        });
        <?php
    }
    ?>
}

$(function() {
    var previousValues = Array(globals.getMaxWeeks()+1);     // 0-based, index is the week number 1-21 (now 22)
    $('.team-pick').on('focus', function() {
        // save the previous logo and selected value for the dropdown in case there is an error changing it
        var $select = $(this),
            week = parseInt($select.data('week'), 10),
            team = $select.val(),
            logo = $('.logo-container[week=' + week + ']').html();
        previousValues[week] = {
            team: team,
            logo: logo
        };
    }).on('change', function() {
        var $this = $(this),
            week = $this.data('week'),
            team = $this.val(),
            data = {
                user:       <?php echo $pickUserId; ?>,   // update this to the user being edited so that superadmins can change other users' picks
                week:       week,
                team:       team,
                hardcore:   <?php echo (isHardcoreMode() ? 1 : 0);?>
            },
            $logoContainer = $('.logo-container[week=' + week + ']'),
            fnError;

        fnError = function(msg) {
            var $error = $('<span class="text-danger">' + msg + '</span>');
            $logoContainer.html($error);
            setTimeout(function() {
                $error.fadeOut('default', function() {
                    var previousValue = previousValues[week];
                    if (typeof previousValue !== 'undefined') {
                        $logoContainer.hide().html(previousValue.logo).fadeIn();
                        $this.val(previousValue.team);
                    } else {
                        $this.val('');
                    }
                });
            }, 5000);
        };
        
        $this.prop('disabled', true);
        $logoContainer.find('.text-danger').remove();
        $logoContainer.find('.logo').fadeOut();
        $.ajax({
            url: '<?php echo $this->createAbsoluteUrl('pick/save')?>',
            type: 'POST',
            data: data,
            success: function(response) {
                var $newLogo;
                if (response.hasOwnProperty('error') && response.error !== '') {
                    fnError(response.error);
                } else {
                    picksByWeek[week] = parseInt(team, 10);
                    hardcoreAdjust();
                    $logoContainer.empty();
                    if (response.hasOwnProperty('team') && response.team) {
                        $newLogo = $('<div/>')
                            .addClass('logo logo-medium')
                            .css('background-position', globals.getTeamLogoOffset(response.team, 'small'))
                            .attr('title', response.team.longname)
                            .hide();
                        $logoContainer.append($newLogo);
                        $newLogo.fadeIn();
                    }
                }
            },
            error: function() {
                fnError('An error ocurred, please try again.');
            },
            complete: function() {
                $this.prop('disabled', false);
            },
            dataType: 'json'
        });
    });
    
    hardcoreAdjust();

    <?php
    if ($delayAmount) {
        ?>
        (function() {
            var seconds = <?php echo $delayAmount;?>,
                fnCheck = function() {
                    if (--seconds < 1) {
                        $('#unpaid-delay').hide();
                        $('#make-pick-table').show();
                    } else {
                        $('#delay-timer').html(seconds);
                        setTimeout(fnCheck, 1000);
                    }
                };
            setTimeout(fnCheck, 1000);
        })();
        <?php
    }
    ?>
});
</script>

<div class="container">
    <h2>Make Picks<?php
    if (isSuperadmin() && $pickUserId != userId()) {
        echo ' * FOR ' . $pickUser->username . ' *';
    }
    ?></h2>
    <?php
    if ($badUser) {
    }
    if ($delayAmount) {
        ?>
        <div id="unpaid-delay">
            <p style="width:50%;margin:0 auto;">
                Hello there, user!  I've noticed you haven't paid your entry fee yet.  As a penance, you will endure 10 seconds of waiting for every
                week of the NFL season thus far.  Since it is week <?php echo getCurrentWeek();?>, the wait is <?php echo $delayAmount?> seconds.
                Here's a countdown timer you can watch to pass the time!<br />
            </p>
            <div class="text-center" id="delay-timer" style="font-weight:bold;font-size:200%"><?php echo $delayAmount;?></div>
            <br />
                <p style="width:50%;margin:0 auto;">
                Please pay your $25 entry fee via Venmo (@Kirk-Hemmen) or Paypal (kirk.hemmen@gmail.com), or mail it to:<br />
                Kirk Hemmen<br />
                10974 Goose Lake Rd<br />
                Champlin, MN 55316
            </p>
        </div>
        <?php
    }
    if ($badUser) {
        ?>
        The user you have selected does not exist or is not active for <?php echo (isHardcoreMode() ? 'hardcore' : 'normal');?> mode this year.
        <?php
    } else {
        ?>
        <div id="make-pick-table" class="table-responsive"<?php echo ($delayAmount ? ' style="display:none;"' : '')?>>
            <table class="table">
                <thead>
                    <tr>
                        <th>Week</th>
                        <th colspan="2">Pick</th>
                        <th colspan="2">Lock Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($pickByWeek as $week => $pick) {
                        $class = '';
                        if (!is_null($pick['incorrect'])) {
                            $class = $pick['incorrect'] == 1 ? 'danger' : 'success';
                        }
                        ?>
                        <tr class="<?php echo $class?>">
                            <td><?php echo getWeekName($week);?></td>
                            <td>
                                <select class="form-control team-pick" data-week="<?php echo $week;?>"<?php echo isLocked($week) && !isSuperadmin() ? ' disabled="disabled"' : '';?>>
                                    <option value="">Select Loser...</option>
                                    <?php
                                    foreach ($teams as $team) {
                                        echo createOption($team['id'], $team['longname'], $pick['teamid']);
                                    }
                                    ?>
                                </select>
                            </td>
                            <td class="logo-container" week="<?php echo $week;?>">
                            <?php
                            if ($pick && $pick['team']) {
                                ?>
                                <div class="logo logo-medium" style="background-position:<?php echo getTeamLogoOffset($pick['team'], 'medium');?>" title="<?php echo $pick['team']['longname'];?>"></div>
                                <?php
                            } else {
                                echo '&nbsp;';
                            }
                            ?>
                            </td>
                            <td><?php echo getLockTime($week, true)?></td>
                            <td>
                                <?php
                                if (isLocked($week)) {
                                    echo '<strong>LOCKED</strong>';
                                } else {
                                    $now = new DateTime();
                                    $difference = $now->diff(getLockTime($week));
                                    echo $difference->format('%a days, %h hours, %i minutes');
                                }
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    ?>
</div>
