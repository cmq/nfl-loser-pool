<?php
// KDHTODO provide a delete button for superadmins?
// KDHTODO provide an edit button for superadmins?

$thisUserLiked = false;
$userLikes = array();
$numLikes = count($talk->likes);
foreach ($talk->likes as $like) {
    if ($like->user->id == userId()) {
        $thisUserLiked = true;
    }
    $userLikes[$like->user->id] = $like->user->username;
}

?>
<div class="panel talk <?php echo ($talk->admin ? 'panel-danger' : ($talk->at && $talk->at->id == userId() ? 'panel-primary' : 'panel-default'));?>">
    <div class="panel-heading">
        <div class="talk-time small">
            <?php echo date('n:ia \o\n D, M jS, Y', strtotime($talk->postedon));?>
        </div>
        <?php
        // KDHTODO send these links to the relative users' profiles
        if ($talk->admin) {
            echo '<strong><a href="#"><img class="avatar" src="' . getUserAvatarUrl($talk->user->id, $talk->user->avatar_ext, true) . '" /></a> ADMINISTRATIVE MESSAGE</strong>';
        } else {
            echo '<a href="#"><img class="avatar" src="' . getUserAvatarUrl($talk->user->id, $talk->user->avatar_ext, true) . '" />'. $talk->user->username . '</a> said';
            if ($talk->at) {
                echo ' to <a href="#">' . $talk->at->username . '<img class="avatar" src="' . getUserAvatarUrl($talk->at->id, $talk->at->avatar_ext, true) . '" /></a>';
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
            <div class="likes small<?php echo ($numLikes ? '' : ' hidden');?>" data-talkid="<?php echo $talk->id;?>" data-likes="<?php echo htmlentities(CJSON::encode($userLikes));?>">
                Liked by <?php echo $numLikes;?> User<?php echo ($numLikes == 1 ? '' : 's');?>
            </div>
            <div class="like-button<?php echo ($thisUserLiked ? ' active' : '')?>" data-talkid="<?php echo $talk->id;?>"></div>
        </div>
    </div>
</div>
<?php
