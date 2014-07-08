<?php
$pickByWeek = array();
for ($i=1; $i<21; $i++) {
    $pickByWeek[$i] = null;
}
foreach ($picks as $pick) {
    $pickByWeek[$pick['week']] = $pick;
}
?>
<script>
$(function() {
    $('.team-pick').on('change', function() {
        var $this = $(this),
            week = $(this).data('week'),
            team = $(this).val(),
            data = {
                user: <?php echo userId(); ?>,   // KDHTODO update this to the user being edited so that superadmins can change other users' picks
                week: week,
                team: team
            },
            $logoContainer = $('.logo-container[week=' + week + ']'),
            fnError;

        fnError = function(msg) {
            var $error = $('<span class="text-danger">' + msg + '</span>');
            $logoContainer.html($error);
            setTimeout(function() {
                $error.fadeOut();
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
});
</script>

<div class="container">
    <div class="table-responsive">
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
                foreach ($pickByWeek as $pick) {
                    $class = '';
                    if (!is_null($pick['incorrect'])) {
                        $class = $pick['incorrect'] == 1 ? 'danger' : 'success';
                    }
                    ?>
                    <tr class="<?php echo $class?>">
                        <td><?php echo getWeekName($pick['week']);?></td>
                        <td>
                            <select class="form-control team-pick" data-week="<?php echo $pick['week']?>"<?php echo isLocked($pick['week']) && !isSuperadmin() ? ' disabled="disabled"' : '';?>>
                                <option value="">Select Loser...</option>
                                <?php
                                foreach ($teams as $team) {
                                    echo createOption($team['id'], $team['longname'], $pick['teamid']);
                                }
                                ?>
                            </select>
                        </td>
                        <td class="logo-container" week="<?php echo $pick['week'];?>"><div class="logo logo-medium" style="background-position:<?php echo getTeamLogoOffset($pick['team'], 'medium');?>" title="<?php echo $pick['team']['longname'];?>"></div></td>
                        <td><?php echo getLockTime($pick['week'], true)?></td>
                        <td>
                            <?php
                            if (isLocked($pick['week'])) {
                                echo '<strong>LOCKED</strong>';
                            } else {
                                $now = new DateTime();
                                $difference = $now->diff(getLockTime($pick['week']));
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
</div>