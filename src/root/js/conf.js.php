<script>
var CONF = {
    userId:                 <?php echo userId()?>,
    isAdmin:                <?php echo (isAdmin() ? 'true' : 'false')?>,
    currentYear:            <?php echo param('currentYear')?>,
    currentWeek:            <?php echo param('currentWeek')?>,
    headerWeek:             <?php echo param('headerWeek')?>,
    earliestYear:           <?php echo param('earliestYear')?>,
    avatarWebDirectory:     '<?php echo param('avatarWebDirectory')?>',
    winnerTrophyUrlPrefix:  '<?php echo param('winnerTrophyUrlPrefix')?>',
    boardPollerInterval:    <?php echo param('boardPollerInterval')?>,
    url: {
        poll:           '<?php echo $this->createAbsoluteUrl('site/poll')?>',
        showCorrect:    '<?php echo $this->createAbsoluteUrl('admin/showCorrect')?>'
    }
};
</script>