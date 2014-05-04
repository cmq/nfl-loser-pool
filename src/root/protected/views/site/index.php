<script src="<?php echo baseUrl('/js/Board.js'); ?>"></script>
<script>
$(function() {
    window.board = new Board({
        table: $('#pick-board'),
        poll:  true
    });
});
</script>


<div class="container">
    <h4>Most Recent Talk</h4>
    <?php
    foreach ($talk as $t) {
        $this->renderPartial('//_partials/talk', array('talk'=>$t));
    }
    ?>
</div>
<div class="container-fluid">
    
    <h5>Debug Current Week / Header Week: <?php echo getCurrentWeek();?> / <?php echo getHeaderWeek();?></h5>
    
    <div class="table-responsive">
        <table class="picks table table-striped table-bordered" id="pick-board">
        </table>
    </div>
    
</div>