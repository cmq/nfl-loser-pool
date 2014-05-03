var CONF = {
    currentYear:            <?php echo param('currentYear')?>,
    currentWeek:            <?php echo param('currentWeek')?>,
    headerWeek:             <?php echo param('headerWeek')?>,
    earliestYear:           <?php echo param('earliestYear')?>,
    avatarWebDirectory:     '<?php echo param('avatarWebDirectory')?>',
    boardPollerInterval:    <?php echo param('boardPollerInterval')?>,
    url: {
        poll: '<?php echo $this->createAbsoluteUrl('site/poll')?>'
    }
};