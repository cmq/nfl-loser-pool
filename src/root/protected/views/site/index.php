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
        userPaid: <?php echo isPaid() ? 'true' : 'false'?>,
        viewOptions: {
            collapseHistory: <?php echo userField('collapse_history') ? 'true' : 'false'?>,
            showBadges:      <?php echo userField('show_badges') ? 'true' : 'false'?>,
            showMov:         <?php echo userField('show_mov') ? 'true' : 'false'?>,
            showLogos:       <?php echo userField('show_logos') ? 'true' : 'false'?>
        }
    });

    $(document).on('show.bs.collapse', '.panel-collapse', function () {
        globals.setSectionCollapsed($(this).attr('id'), false);
    });
    $(document).on('hide.bs.collapse', '.panel-collapse', function () {
        globals.setSectionCollapsed($(this).attr('id'), true);
    });

    if (!globals.isSectionCollapsed('collapseTalk')) {
        $('#collapseTalk').addClass('in');
    }
    if (!globals.isSectionCollapsed('collapseBandwagon')) {
        $('#collapseBandwagon').addClass('in');
    }
});
</script>

<!--
<h5>Debug Current Week / Header Week: <?php echo getCurrentWeek();?> / <?php echo getHeaderWeek();?></h5>
-->

<div class="container-fluid">
    
    <h2>Loser Pool Home</h2>
    
    <div class="panel-group">
        <?php
        if (count($talk)) {
            $recent = null;
            foreach ($talk as $t) {
                if (!$t->sticky) {
                    $recent = $t;
                    break;
                }
            }
            ?>
            <div class="panel panel-primary">
                <div class="accordian-link panel-heading" data-toggle="collapse" href="#collapseTalk">
                    <h4 class="panel-title">Recent Posts<?php echo ($recent ? ' (Most Recent: ' . formatDateTimeForUserTimezone(new DateTime($recent->postedon)) . ')' : '')?></h4>
                </div>
                <div id="collapseTalk" class="panel-collapse collapse">
                    <div class="panel-body">
                        <?php
                        $hasSticky    = false;
                        $hasNonSticky = false;
                        foreach ($talk as $t) {
                            if ($t->sticky) {
                                if (!$hasSticky) {
                                    echo '<h3 class="text-center">Stickied posts...</h3>';
                                }
                                $hasSticky = true;
                            } else if ($hasSticky && !$hasNonSticky) {
                                echo '<br /><h3 class="text-center">Other recent posts...</h3>';
                                $hasNonSticky = true;
                            }
                            $this->renderPartial('//_partials/Talk', array('talk'=>$t));
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
        }
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
                                        $otherRiders = array();
                                        foreach ($boardData as $user) {
                                            if ($user->id == $thisWeekBandwagon->chief->id) continue;
                                            foreach ($user->picks as $pick) {
                                                if ($pick->week == getCurrentWeek() && $pick->teamid == $thisWeekBandwagon->team->id) {
                                                    $weeksOn  = $pick->weeks_on_bandwagon;
                                                    $rider    = array('user'=>$user, 'weeksOn'=>$weeksOn);
                                                    $position = count($otherRiders);
                                                    // find where in the otherRiders array this user fits
                                                    for ($i=0; $i<count($otherRiders); $i++) {
                                                        if ($weeksOn > $otherRiders[$i]['weeksOn']) {
                                                            $position = $i;
                                                            break;
                                                        } else if ($weeksOn == $otherRiders[$i]['weeksOn'] && $user->power_ranking < $otherRiders[$i]['user']->power_ranking) {
                                                            $position = $i;
                                                            break;
                                                        }
                                                    }
                                                    array_splice($otherRiders, $i, 0, array($rider));
                                                }
                                            }
                                        }
                                        foreach ($otherRiders as $rider) {
                                            echo '<div class="profile-bubble">' . getAvatarProfileLink($rider['user'], true, true) . " ({$rider['weeksOn']})</div>";
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