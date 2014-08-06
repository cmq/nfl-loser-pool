<script>
var CONF = {
    userId:                 <?php echo userId()?>,
    username:               '<?php echo addslashes(userField('username'))?>',
    isAdmin:                <?php echo (isAdmin() ? 'true' : 'false')?>,
    isSuperadmin:           <?php echo (isSuperadmin() ? 'true' : 'false')?>,
    currentYear:            <?php echo param('currentYear')?>,
    currentWeek:            <?php echo param('currentWeek')?>,
    headerWeek:             <?php echo param('headerWeek')?>,
    earliestYear:           <?php echo param('earliestYear')?>,
    avatarWebDirectory:     '<?php echo param('avatarWebDirectory')?>',
    winnerTrophyUrlPrefix:  '<?php echo param('winnerTrophyUrlPrefix')?>',
    boardPollerInterval:    <?php echo param('boardPollerInterval')?>,
    entryFee:               <?php echo param('entryFee')?>,
    movFee:                 <?php echo param('movFee')?>,
    movFirstYear:           <?php echo param('movFirstYear')?>,
    url: {
        poll:           '<?php echo $this->createAbsoluteUrl('site/poll')?>',
        showCorrect:    '<?php echo $this->createAbsoluteUrl('admin/showCorrect')?>',
        like:           '<?php echo $this->createAbsoluteUrl('talk/like')?>',
        profile:        function(id) {
            return '<?php echo $this->createAbsoluteUrl('stats/profile', array('id'=>0))?>'.replace('0', id);
        }
    },
    powerMultipliers: {
        pointsPerFirstPlace:    <?php echo param('powerMultipliers')['pointsPerFirstPlace'];?>,
        pointsPerSecondPlace:   <?php echo param('powerMultipliers')['pointsPerSecondPlace'];?>
    }
};
</script>