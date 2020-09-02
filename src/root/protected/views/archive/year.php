<script src="<?php echo baseUrl('/js/Board.js'); ?>"></script>
<script>
$(function() {
    window.board = new Board({
        container: $('#pick-board'),
        board: <?php echo CJSON::encode($boardData);?>,
        bandwagon: <?php echo CJSON::encode($bandwagon);?>,
        bestWorst: <?php echo CJSON::encode($bestWorst);?>,
        poll:  false,
        order: '<?php echo ($year == param('earliestYear') ? 'stayAlive' : 'money');?>',
        collapsable: true,
        showPayout: false,
        currentYear: <?php echo $year;?>,
        currentWeek: <?php echo ($year == param('earliestYear') ? 9 : 21);?>,
        viewOptions: {
            collapseHistory: <?php echo userField('collapse_history') ? 'true' : 'false'?>,
            showBadges:      <?php echo userField('show_badges') ? 'true' : 'true'?>,
            showMov:         <?php echo userField('show_mov') ? 'true' : 'true'?>,
            showLogos:       <?php echo userField('show_logos') ? 'true' : 'true'?>
        }
    });
});
</script>


<div class="container-fluid">

    <h2><?php echo $year;?> Archived <?php echo ((int) getRequestParameter('hc', 0) > 0 ? ' Hardcore' : 'Normal');?> Season</h2>

    <div class="panel-group">
        <?php
        if (count($talk)):
            ?>
            <div class="panel panel-primary">
                <div class="accordian-link panel-heading" data-toggle="collapse" href="#collapseTalk">
                    <h4 class="panel-title">Archived Posts (<?php echo count($talk);?>)</h4>
                </div>
                <div id="collapseTalk" class="panel-collapse collapse">
                    <div class="panel-body">
                        <?php
                        foreach ($talk as $t) {
                            $this->renderPartial('//_partials/Talk', array('talk'=>$t));
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
        endif;
        ?>
        <div id="pick-board" style="margin-top:5px;"></div>
    </div>
    
</div>
