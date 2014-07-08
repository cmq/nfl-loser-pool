<?php
$thisWeekBandwagon = null;
foreach ($bandwagon as $b) {
    if ($b['week'] == getCurrentWeek()) {
        $thisWeekBandwagon = $b;
        break;
    }
}
?>
<script src="<?php echo baseUrl('/js/Board.js'); ?>"></script>
<script>
$(function() {
    window.board = new Board({
        container: $('#pick-board'),
        poll:  true,
        order: 'record',
        collapsable: true,
        viewOptions: {
            collapseHistory: <?php echo userField('collapse_history') ? 'true' : 'false'?>,
            showBadges:      <?php echo userField('show_badges') ? 'true' : 'true'?>,
            showMov:         <?php echo userField('show_mov') ? 'true' : 'true'?>,
            showLogos:       <?php echo userField('show_logos') ? 'true' : 'true'?>
        }
    });
});
</script>


<h5>Debug Current Week / Header Week: <?php echo getCurrentWeek();?> / <?php echo getHeaderWeek();?></h5>


<div class="container-fluid">
    
    <h2>Loser Pool Home</h2>
    
    <div class="panel-group">
        <div class="panel panel-primary">
            <div class="accordian-link panel-heading" data-toggle="collapse" href="#collapseTalk">
                <h4 class="panel-title">Recent Posts (<?php echo count($talk);?>)</h4>
            </div>
            <div id="collapseTalk" class="panel-collapse collapse">
                <div class="panel-body">
                    <?php
                    foreach ($talk as $t) {
                        $this->renderPartial('//_partials/talk', array('talk'=>$t));
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
        if ($thisWeekBandwagon):
            ?>
            <div class="panel panel-primary">
                <div class="accordian-link panel-heading" data-toggle="collapse" href="#collapseBandwagon">
                    <h4 class="panel-title">Bandwagon (Chief: <?php echo $thisWeekBandwagon->chief->username;?>)</h4>
                </div>
                <div id="collapseBandwagon" class="panel-collapse collapse">
                    <div class="panel-body">
                        <div class="col-xs-12">
                            <h4 class="text-center">Bandwagon Team for <?php echo getWeekName(getCurrentWeek(), true);?>:  <strong><?php echo $thisWeekBandwagon->team->longname;?></strong></h4>
                        </div>
                        <div id="the-bandwagon" class="clearfix"> 
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 pull-right">
                                    <img src="/images/bandwagon-large.png" />
                                    <?php
                                    $weeksOn = 1;
                                    foreach ($boardData as $user) {
                                        if ($user->id == $thisWeekBandwagon->chief->id) {
                                            foreach ($user->picks as $pick) {
                                                if ($pick->week == getCurrentWeek() && $pick->teamid == $thisWeekBandwagon->team->id) {
                                                    $weeksOn = $pick->weeks_on_bandwagon;
                                                    break;
                                                }
                                            }
                                            break;
                                        }
                                    }
                                    ?>
                                    <span class="chief">CHIEF: <?php echo getAvatarProfileLink($thisWeekBandwagon->chief, true) . " ($weeksOn" . ' consecutive weeks)';?></span>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 pull-left">
                                    <h5>Other riders....</h5>
                                    <div class="others">
                                        <?php
                                        foreach ($boardData as $user) {
                                            if ($user->id == $thisWeekBandwagon->chief->id) continue;
                                            foreach ($user->picks as $pick) {
                                                if ($pick->week == getCurrentWeek() && $pick->teamid == $thisWeekBandwagon->team->id) {
                                                    $weeksOn = $pick->weeks_on_bandwagon;
                                                    echo '<div class="profile-bubble">' . getAvatarProfileLink($user, true, true) . " ($weeksOn)</div>";
                                                    break;
                                                }
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>    
                        </div>                
                    </div>
                </div>
            </div>
            <?php
        endif;
        ?>
        <div id="pick-board" style="margin-top:5px;"></div>
    </div>
    
</div>