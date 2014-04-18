<?php
// KDHTODO format and use classes instead of inline styles
// KDHTODO format admin posts vs regular
// KDHTODO draw more attention to @ posts that are directed at the current user
// KDHTODO show "likes"
// KDHTODO provide a delete button for superadmins?
?>
<div style="border: black 1px solid; width: 500px;">
    <?php
    echo 'By: ' . $talk->user->username . '<br />';
    if ($talk->at) {
        echo '<strong>@' . $talk->at->username . '</strong><br />';
    }
    // KDHTODO need to change \n to <br>
    // KDHTODO format posted date 
    echo $talk->postedon . '<br />' . $talk->post;
    ?>
</div>
<?php
