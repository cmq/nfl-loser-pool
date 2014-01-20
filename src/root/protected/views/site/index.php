<?php
/* @var $this SiteController */

// testing database connection.... WORKS!
/*
$sql = 'select username from user where active = 1 order by username';
$data = Yii::app()->db->createCommand($sql)->query();
foreach ($data as $row) {
    echo $row['username'] . '<br />';
}

// KDHTODO handle AJAX errors where user is logged out

// KDHTODO find a better way to get at all user record data instead of Yii::app()->user->record['fieldname'];
// KDHTODO since Yii::app()->user is only updated on login, need to update relevant session information when the user changes their login name or password without having them have to log out and back in again
*/

$this->pageTitle=Yii::app()->name;
$user = Yii::app()->user;
?>

<h1>Welcome to <i><?php echo CHtml::encode(Yii::app()->name); ?></i></h1>

<h2>You are logged in as <?php echo ($user->isGuest ? 'guest' : 'registered user ' . $user->id . ' (' . $user->record['username'] . ' - ' . $user->record['firstname'] . ' ' . $user->record['lastname'] . ')')?></h2>

<pre>
<?php
if (Yii::app()->user->isGuest) {
    ?>You are a guest.  What does that mean you can access?  Past seasons, perhaps?  But you may want to <a href="<?php echo $this->createUrl('site/login')?>">log in</a><br /><?php
} else {
    ?><a href="<?php echo $this->createUrl('site/logout')?>">Logout</a><br /><?php
}
?>
</pre>
