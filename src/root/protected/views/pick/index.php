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
            };
        $this.prop('disabled', true);
        $.ajax({
            url: '<?php echo $this->createAbsoluteUrl('pick/save')?>',
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.hasOwnProperty('error') && response.error !== '') {
                    // KDHTODO do something different
                    alert(response.error);
                } else {
                    // KDHTODO set new logo
                }
            },
            error: function() {
                // KDHTODO do something different
                alert('An error ocurred, please try again.');
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
                    <th>Lock Time</th>
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
                            <!--
                            // KDHTODO select what the user had
                            // KDHTODO add a column for the logo
                            // KDHTODO make a fancier select like the main page, to select a team by logo in a small modal?
                            // KDHTODO AJAX call to save when the dropdown changes
                            // KDHTODO disable dropdowns that are locked
                            -->
                            <select class="form-control team-pick" data-week="<?php echo $pick['week']?>">
                                <option value="">Select Loser...</option>
                                <?php
                                foreach ($teams as $team) {
                                    echo createOption($team['id'], $team['longname'], $pick['teamid']);
                                }
                                ?>
                            </select>
                        </td>
                        <!-- KDHTODO populate -->
                        <td>Logo</td>
                        <!-- KDHTODO populate -->
                        <td><?php echo getLockTime($pick['week'], true)?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>