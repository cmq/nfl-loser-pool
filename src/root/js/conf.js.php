<?php
$multipliers = param('powerMultipliers');
?>
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
    earliestYear17Games:    <?php echo param('earliestYear17Games')?>,
    avatarWebDirectory:     '<?php echo param('avatarWebDirectory')?>',
    winnerTrophyUrlPrefix:  '<?php echo param('winnerTrophyUrlPrefix')?>',
    boardPollerInterval:    <?php echo param('boardPollerInterval')?>,
    entryFee:               <?php echo param('entryFee')?>,
    movFee:                 <?php echo param('movFee')?>,
    movFirstYear:           <?php echo param('movFirstYear')?>,
    maxWeeks:               <?php echo getMaxWeeks()?>,
    url: {
        poll:           '<?php echo $this->createAbsoluteUrl('site/poll')?>',
        showCorrect:    '<?php echo $this->createAbsoluteUrl('admin/showCorrect')?>',
        like:           '<?php echo $this->createAbsoluteUrl('talk/like')?>',
        profile:        function(id) {
            return '<?php echo $this->createAbsoluteUrl('stats/profile', array('id'=>0))?>'.replace('0', id);
        },
        about:          function(page) {
            return '<?php echo $this->createAbsoluteUrl('about/page')?>'.replace('page', page);
        },
        archive:        function(yr) {
            return '<?php echo $this->createAbsoluteUrl('archive/year', array('y'=>0))?>'.replace('0', yr);
        }
    },
    powerMultipliers: {
        pointsPerFirstPlace:    <?php echo $multipliers['pointsPerFirstPlace'];?>,
        pointsPerSecondPlace:   <?php echo $multipliers['pointsPerSecondPlace'];?>
    }
};
</script>
