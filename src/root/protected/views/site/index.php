<script src="<?php echo baseUrl('/js/model/Badge.js'); ?>"></script>
<script src="<?php echo baseUrl('/js/model/Pick.js'); ?>"></script>
<script src="<?php echo baseUrl('/js/model/Team.js'); ?>"></script>
<script src="<?php echo baseUrl('/js/model/User.js'); ?>"></script>
<script src="<?php echo baseUrl('/js/model/UserYear.js'); ?>"></script>
<?php
/* @var $this SiteController */

// KDHTODO remove all these test

// testing database connection.... WORKS!
/*
$sql = 'select username from user where active = 1 order by username';
$data = Yii::app()->db->createCommand($sql)->query();
foreach ($data as $row) {
    echo $row['username'] . '<br />';
}
*/

// testing relational active record.... WORKS, but have to limit it or exceeds memory and craps out
/*
$records = User::model()->with(array(
        'picks' => array(
            'select' => 'teamid',
            'condition' => 'yr=2013',
        )
    ))->findAll(array(
        'select'    => 't.username',
        'condition' => '1 = 1',
        'order'     => '',
    ));
var_dump($records);
*/


// testing m:m relation... kind of a mess, but it works.  This is how you have to get at badges
// instead of using a normal MANY_MANY relationship, because we need to get data out of the join table (userbadge)
/*
$records = User::model()->with(array(
        'userBadges' => array(
            'select' => 'display',
            'with' => array(
                'badge' => array(
                    'select' => array('name', 'img', 'display'),
                ),
            ),
        ),
    ))->findAll(array(
        'condition' => 't.id = 1',
    ));
echo $records[0]->userBadges[0]->display;
echo $records[0]->userBadges[0]->badge->name;
echo '<pre>';
var_dump($records[0]);
echo '</pre>';
*/


///////////////////////////////////////////////////////////////////
/*
// KDHTODO handle AJAX errors where user is logged out

// KDHTODO find a better way to get at all user record data instead of Yii::app()->user->record['fieldname'];
// KDHTODO since Yii::app()->user is only updated on login, need to update relevant session information when the user changes their login name or password without having them have to log out and back in again
*/

$this->pageTitle = Yii::app()->name;
$user = Yii::app()->user;
?>

<h1>Welcome to <i><?php echo CHtml::encode(Yii::app()->name); ?></i></h1>

<h2>You are logged in as <?php echo ($user->isGuest ? 'guest' : 'registered user ' . $user->id . ' (' . $user->record['username'] . ' - ' . $user->record['firstname'] . ' ' . $user->record['lastname'] . ')')?></h2>
<?php
if (Yii::app()->user->isGuest) {
    ?>You are a guest.  What does that mean you can access?  Past seasons, perhaps?  But you may want to <a href="<?php echo $this->createUrl('site/login')?>">log in</a><br /><?php
} else {
    ?><a href="<?php echo $this->createUrl('site/logout')?>">Logout</a><br /><?php
}

echo 'Total of ' . count($boardData) . ' board data records';
// KDHTODO put this information into javascript for use with Angular
// KDHTODO output angular template
//echo $boardData[0]->username;
//echo $boardData[0]->userYears[0]->paid;
//echo $boardData[0]->userBadges[0]->display;

?>




<script>
// load data from server into JS
var boardData = <?php echo CJSON::encode($boardData);?>;
// test load the first user into our sample User JS Model
var kirktest = new User(boardData[0]);





/****************************************************************/
//Things below here are testing AngularJS
/****************************************************************/
function TestCtrl($scope) {
    $scope.user = kirktest;

    $scope.testOutput = function() {
        console.log($scope.user.username);
    };
}
</script>

<div ng-controller="TestCtrl">
    <h3>{{user.username}}</h3>
    <input type="text" ng-model="user.username" /><br />
    <button ng-click="testOutput();">Test Output</button>
</div>

