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

// KDHTODO since Yii::app()->user is only updated on login, need to update relevant session information when the user changes their login name or password without having them have to log out and back in again
*/

$this->pageTitle = Yii::app()->name;
$user = Yii::app()->user;
?>

<h1>Welcome to <i><?php echo CHtml::encode(Yii::app()->name); ?></i></h1>

<h2>You are logged in as <?php echo ($user->isGuest ? 'guest' : 'registered user ' . $user->id . ' (' . userField('username') . ' - ' . userField('firstname') . ' ' . userField('lastname') . ')')?></h2>
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
/****************************************************************/
//Things below here are the real app
/****************************************************************/
loserpool.controller('BoardCtrl', ['$scope', function($scope) {
    var i;
    $scope.range = [];
    $scope.order = 'username';
    $scope.board = <?php echo CJSON::encode($boardData);?>;

    for (i=1; i<=21; i++) {
        $scope.range.push(i);
    }

    // KDHTODO extract this into a more general place?
    $scope.weekname = function(i) {
        return globals.getWeekName(i);
    }
    $scope.setOrder = function(order) {
        $scope.order = 'picks[' + order + '].team.shortname';
    }
}]);
</script>

<div ng-controller="BoardCtrl">
Debug Order: {{order}}<br />
    <table border="1">
        <thead>
            <tr>
                <th>&nbsp;</th>
                <!-- KDHTODO add support for reversing the sort order (should work by simply prefixing the sort properties with a minus sign) -->
                <th ng-click="order = 'username'">User</th>
                <th ng-repeat="i in range" ng-click="setOrder(i-1)">{{weekname(i)}}</th>
            </tr>
        </thead>
        <tbody>
            <tr ng-repeat="user in board | orderBy:[order,'username']">   <!-- KDHTODO have sort order secondary sort be record before username -->
                <td>{{$index+1}}</td>
                <td>
                    {{user.username}}
                    <!-- KDHTODO format this similar to the old site (extract into directive or something?) -->
                    <!-- KDHTODO add "alt" tags and title attributes -->
                    <img ng-repeat="userBadge in user.userBadges | orderBy:'badge.zindex'" src="{{userBadge.badge.img}}" alt="{{userBadge.badge.zindex}}" title="{{userBadge.badge.zindex}}" />
                </td>
                <td ng-repeat="pick in user.picks">{{pick.team.shortname}} {{$index}}</td>
                <td ng-repeat="i in range" ng-if="i > user.picks.length">*</td>
            </tr>
        </tbody>
    </table>
</div>