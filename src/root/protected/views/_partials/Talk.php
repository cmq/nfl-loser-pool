<?php
$thisUserLiked = false;
$userLikes = array();
$numLikes = count($talk->likes);
foreach ($talk->likes as $like) {
    if ($like->user->id == userId()) {
        $thisUserLiked = true;
    }
    $userLikes[$like->user->id] = $like->user->username;
}

// adjust the posted time for the user's timezone
// NOTE:  locktime comes back in mountain time, but our user timezone modifications are based on central time, so start with +1
$postTime = new DateTime($talk->postedon);
$hours = +1;
if (userField('timezone')) {
    $hours += (int) userField('timezone');
}
// figure out how the user's dst settings apply
if (date('I')) {
    // it is currently daylight savings time
    if (!userField('use_dst')) {
        // but the user doesn't want to use daylight savings time
        $hours -= 1;
    }
}
if ($hours > 0) {
    $postTime->add(new DateInterval("PT{$hours}H"));
} else if ($hours < 0) {
    $hours *= -1;
    $postTime->sub(new DateInterval("PT{$hours}H"));
}

?>
<div class="panel talk <?php echo ($talk->admin ? 'panel-warning' : ($talk->at && $talk->at->id == userId() ? 'panel-success' : 'panel-default'));?>">
    <div class="panel-heading">
        <div class="talk-time small">
            <?php echo $postTime->format('g:ia \o\n D, M jS, Y');?>
        </div>
        <?php
        if ($talk->admin) {
            echo getAvatarProfileLink($talk->user) . ' <strong>ADMINISTRATIVE MESSAGE</strong>';
            echo ($talk->sticky ? ' [STICKY]' : '');
        } else {
            echo getAvatarProfileLink($talk->user) . ' ' . getProfileLink($talk->user) . ' said';
            if ($talk->at) {
                echo ' to ' . getProfileLink($talk->at) . ' ' . getAvatarProfileLink($talk->at);
            } else {
                echo '...';
            }
        }
        ?>
    </div>
    <div class="panel-body">
        <?php echo str_replace("\n", '<br />', $talk->post);?>
    </div>
    <div class="panel-footer">
        <div>
            <div class="like-button<?php echo ($thisUserLiked ? ' active' : '')?>" data-talkid="<?php echo $talk->id;?>"></div>
            <div class="spawns-popover likes small<?php echo ($numLikes ? '' : ' hidden');?>" data-talkid="<?php echo $talk->id;?>" data-likes="<?php echo htmlentities(CJSON::encode($userLikes));?>">
                Liked by <?php echo $numLikes;?> User<?php echo ($numLikes == 1 ? '' : 's');?>
            </div>
            <br />
        </div>
    </div>
</div>
<?php
