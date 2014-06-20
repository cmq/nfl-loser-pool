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
            showBadges:      <?php echo userField('show_badges') ? 'true' : 'false'?>,
            showMov:         <?php echo userField('show_mov') ? 'true' : 'false'?>,
            showLogos:       <?php echo userField('show_logos') ? 'true' : 'false'?>
        }
    });
});
</script>


<h5>Debug Current Week / Header Week: <?php echo getCurrentWeek();?> / <?php echo getHeaderWeek();?></h5>
<div class="container-fluid">
    
    
    <div class="panel-group">
        <div class="panel panel-primary">
            <div class="accordian-link panel-heading" data-toggle="collapse" href="#collapseTalk">
                <h4 class="panel-title">Recent Posts</h4>
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
        <div id="pick-board" style="margin-top:5px;"></div>
    </div>
    
</div>