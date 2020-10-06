<?php
$thisWeekBandwagon = null;
foreach ($bandwagon as $b) {
    if ($b['week'] == getCurrentWeek()) {
        $thisWeekBandwagon = $b;
        break;
    }
}

// set up some snapshot data
$hasNormalMode      = userHasNormalMode();
$pickNormal         = 0;
$rightNormal        = 0;
$wrongNormal        = 0;
$weekNormal         = 0;
$userPicksNormal    = Pick::model()->currentNormal()->findAll();
$hasHardcoreMode    = userHasHardcoreMode();
$pickHardcore       = 0;
$rightHardcore      = 0;
$wrongHardcore      = 0;
$weekHardcore       = 0;
$userPicksHardcore  = Pick::model()->currentHardcore()->findAll();

foreach ($userPicksNormal as $pick) {
    if (!is_null($pick['incorrect'])) {
        if ($pick['incorrect'] == 1) {
            $wrongNormal++;
            if ($weekNormal == 0) {
                $weekNormal = $pick['week'];
            }
        } else {
            $rightNormal++;
        }
    }
}
foreach ($userPicksHardcore as $pick) {
    if (!is_null($pick['incorrect'])) {
        if ($pick['incorrect'] == 1) {
            $wrongHardcore++;
            if ($weekHardcore == 0) {
                $weekHardcore = $pick['week'];
            }
        } else {
            $rightHardcore++;
        }
    }
}
if ($hasNormalMode) {
    $pick = Pick::model()->findByAttributes(array(
        'yr'        => getCurrentYear(),
        'week'      => max(1, getHeaderWeek()),
        'userid'    => userId(),
        'hardcore'  => 0
    ));
    if ($pick) {
        $pickNormal = Team::model()->find(array('condition'=>"id = " . $pick->teamid));
    }
}
if ($hasHardcoreMode) {
    $pick = Pick::model()->findByAttributes(array(
        'yr'        => getCurrentYear(),
        'week'      => max(1, getHeaderWeek()),
        'userid'    => userId(),
        'hardcore'  => 1
    ));
    if ($pick) {
        $pickHardcore = Team::model()->find(array('condition'=>"id = " . $pick->teamid));
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
    $('button.switchMode').on('click', function(e) {
        var $button = $(this),
            oldHtml = $button.html(),
            mode    = $button.data('mode');
        e.preventDefault();
        $button.prop('disabled', true).html('Switching...');
        $.ajax({
            url:        '<?php echo Yii::app()->createAbsoluteUrl('site/switch')?>',
            data:       {
                            mode:   mode
                        },
            type:       'post',
            cache:      false,
            success:    function(response) {
                            if (response.hasOwnProperty('error') && response.error != '') {
                                alert(response.error);
                                $button.prop('disabled', false).html(oldHtml);
                            } else {
                                window.location.reload();
                            }
                        },
            error:      function() {
                            alert('An error occurred, please try again.');
                            $button.prop('disabled', false).html(oldHtml);
                        },
            dataType:   'json'
        });
            
    });
});
</script>

<!--
<h5>Debug Current Week / Header Week: <?php echo getCurrentWeek();?> / <?php echo getHeaderWeek();?></h5>
-->

<div class="container-fluid">
    
    <h2>Loser Pool Home</h2>
    
    <div class="panel-group">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h4 class="panel-title">User Snapshot</h4>
            </div>
            <div>
                <table class="table table-condensed table-bordered table-striped snapshot">
                    <thead>
                        <tr>
                            <th>&nbsp;</th>
                            <?php echo (isNormalMode() ? '<th>Normal Game Mode</th>' : '<td>Normal Game Mode</td>');?>
                            <?php echo (isHardcoreMode() ? '<th>Hardcore Game Mode</th>' : '<td>Hardcore Game Mode</td>');?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th>Playing?</th>
                            <?php echo (isNormalMode() ? '<th>' : '<td>');?>
                                <?php
                                if ($hasNormalMode) {
                                    echo 'Yes';
                                    if (isNormalMode()) {
                                        echo ' (Currently Active)';
                                    } else {
                                        ?> <button class="switchMode" data-mode="normal">Switch to Normal</button><?php
                                    }
                                } else {
                                    echo 'No';
                                }
                                ?>
                            <?php echo (isNormalMode() ? '</th>' : '</td>');?>
                            <?php echo (isHardcoreMode() ? '<th>' : '<td>');?>
                                <?php
                                if ($hasHardcoreMode) {
                                    echo 'Yes';
                                    if (isHardcoreMode()) {
                                        echo ' (Currently Active)';
                                    } else {
                                        ?> <button class="switchMode" data-mode="hardcore">Switch to Hardcore</button><?php
                                    }
                                } else {
                                    echo 'No';
                                }
                                ?>
                            <?php echo (isNormalMode() ? '</th>' : '</td>');?>
                        </tr>
                        <tr>
                            <th>Paid?</th>
                            <?php echo (isNormalMode() ? '<th>' : '<td>');?>
                            <?php echo ($hasNormalMode ? (isPaid(0) ? 'Yes' : 'No') : 'N/A');?>
                            <?php echo (isNormalMode() ? '</th>' : '</td>');?>
                            <?php echo (isHardcoreMode() ? '<th>' : '<td>');?>
                            <?php echo ($hasHardcoreMode ? (isPaid(1) ? 'Yes' : 'No') : 'N/A');?>
                            <?php echo (isNormalMode() ? '</th>' : '</td>');?>
                        </tr>
                        <tr>
                            <th>Pick for <?php echo getWeekName(max(1, getHeaderWeek()), true)?></th>
                            <?php echo (isNormalMode() ? '<th' : '<td') . ($hasNormalMode && !$pickNormal ? ' class="danger"' : '') . '>';?>
                                <?php
                                if ($hasNormalMode) {
                                    if ($pickNormal) {
                                        ?>
                                        <div class="logo logo-small" style="float:left;background-position:<?php echo getTeamLogoOffset($pickNormal, 'small');?>" title="<?php echo $pickNormal['longname'];?>"></div>
                                        <?php
                                    } else {
                                        echo (isNormalMode() ? '<a href="/pick/index">Not Yet Made</a>' : 'Not Yet Made');
                                    }
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            <?php echo (isNormalMode() ? '</th>' : '</td>');?>
                            <?php echo (isHardcoreMode() ? '<th' : '<td') . ($hasHardcoreMode && !$pickHardcore ? ' class="danger"' : '') . '>';?>
                                <?php
                                if ($hasHardcoreMode) {
                                    if ($pickHardcore) {
                                        ?>
                                        <div class="logo logo-small" style="float:left;background-position:<?php echo getTeamLogoOffset($pickHardcore, 'small');?>" title="<?php echo $pickHardcore['longname'];?>"></div>
                                        <?php
                                    } else {
                                        echo (isHardcoreMode() ? '<a href="/pick/index">Not Yet Made</a>' : 'Not Yet Made');
                                    }
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            <?php echo (isNormalMode() ? '</th>' : '</td>');?>
                        </tr>
                        <tr>
                            <th>First Wrong</th>
                            <?php echo (isNormalMode() ? '<th>' : '<td>');?>
                            <?php echo ($hasNormalMode ? ($weekNormal > 0 ? getWeekName($weekNormal, true) : 'Still Alive') : 'N/A');?>
                            <?php echo (isNormalMode() ? '</th>' : '</td>');?>
                            <?php echo (isHardcoreMode() ? '<th>' : '<td>');?>
                            <?php echo ($hasHardcoreMode ? ($weekHardcore > 0 ? getWeekName($weekHardcore, true) : 'Still Alive') : 'N/A');?>
                            <?php echo (isNormalMode() ? '</th>' : '</td>');?>
                        </tr>
                        <tr>
                            <th>Record</th>
                            <?php echo (isNormalMode() ? '<th>' : '<td>');?>
                            <?php echo ($hasNormalMode ? "$rightNormal-$wrongNormal" : 'N/A');?>
                            <?php echo (isNormalMode() ? '</th>' : '</td>');?>
                            <?php echo (isHardcoreMode() ? '<th>' : '<td>');?>
                            <?php echo ($hasHardcoreMode ? "$rightHardcore-$wrongHardcore" : 'N/A');?>
                            <?php echo (isNormalMode() ? '</th>' : '</td>');?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
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
            // figure out stuff about the chief
            $chiefWeeksOn = 1;
            foreach ($boardData as $user) {
                if ($user->id == $thisWeekBandwagon->chief->id) {
                    foreach ($user->picks as $pick) {
                        if ($pick->week == getCurrentWeek() && $pick->teamid == $thisWeekBandwagon->team->id) {
                            $chiefWeeksOn = $pick->weeks_on_bandwagon;
                            break;
                        }
                    }
                    break;
                }
            }
            // gather the other riders
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
                        array_splice($otherRiders, $position, 0, array($rider));
                    }
                }
            }
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
                            <div class="row hidden-xs hidden-sm">
                                <div class="col-md-6 pull-right text-center">
                                    <div class="chief text-left">CHIEF: <?php echo getAvatarProfileLink($thisWeekBandwagon->chief, true) . " ($chiefWeeksOn" . ' consecutive weeks)';?></div>
                                    <img src="/images/bandwagon-large.png" class="bandwagon-hero" />
                                </div>
                                <div class="col-md-6 pull-left">
                                    <h5>Other riders....</h5>
                                    <div class="others">
                                        <?php
                                        foreach ($otherRiders as $rider) {
                                            echo '<div class="profile-bubble">' . getAvatarProfileLink($rider['user'], true, true) . " ({$rider['weeksOn']})</div>";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="row hidden-md hidden-lg">
                                <div class="col-md-12 text-center">
                                    <div class="chief text-left">CHIEF: <?php echo getAvatarProfileLink($thisWeekBandwagon->chief, true) . " ($chiefWeeksOn" . ' consecutive weeks)';?></div>
                                    <img src="/images/bandwagon-large.png" class="bandwagon-hero" />
                                </div>
                                <div class="col-md-12">
                                    <h5>Other riders....</h5>
                                    <div class="others">
                                        <?php
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
