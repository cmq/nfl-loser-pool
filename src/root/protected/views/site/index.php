<script src="<?php echo baseUrl('/js/model/Badge.js'); ?>"></script>
<script src="<?php echo baseUrl('/js/model/Pick.js'); ?>"></script>
<script src="<?php echo baseUrl('/js/model/Team.js'); ?>"></script>
<script src="<?php echo baseUrl('/js/model/User.js'); ?>"></script>
<script src="<?php echo baseUrl('/js/model/UserYear.js'); ?>"></script>
<?php
// KDHTODO handle AJAX errors where user is logged out
// KDHTODO since Yii::app()->user is only updated on login, need to update relevant session information when the user changes their login name or password without having them have to log out and back in again

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

// KDHTODO move these filters (and maybe the controller?) to a different JS file
loserpool.filter('shortenYear', function() {
    return function (input) {
        var yr = '' + input;
        if (yr.length === 4) {
            return yr.substr(2, 2);
        }
        return yr;
    };
});
loserpool.filter('teamLogoOffset', function() {
    return function (team, size) {
        var multiplier = 50;
        if (size.toLowerCase == 'large') {
            multiplier = 80;
        }
        return '0 -' + (multiplier * team.image_offset) + 'px';
    };
});
</script>

<h5>Debug Current Week / Header Week: <?php echo getCurrentWeek();?> / <?php echo getHeaderWeek();?></h5>
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
                    <div ng-repeat="win in user.wins | orderBy:['place','pot','yr']" class="winnerbadge-wrapper">
                        <!-- KDHTODO make badges clickable to show modal or go to a link? -->
                        <!-- KDHTODO after bootstrap is all up and running, adjust style so year overlays are more readable -->
                        <img src="/images/badges/winnerbadge-{{win.pot}}{{win.place}}.png" />
                        <div class="year pot{{win.pot}}">{{win.yr | shortenYear}}</div>
                    </div>
                    <img ng-repeat="userBadge in user.userBadges | orderBy:'badge.zindex'" src="{{userBadge.badge.img}}" alt="{{userBadge.badge.zindex}}" title="{{userBadge.badge.zindex}}" />
                </td>
                <!-- KDHTODO add margin of victory hovers -->
                <td ng-repeat="pick in user.picks" align="center">
                    <div style="position:relative;">    <!-- KDHTODO add a mov-wrapper class instead of an inline style -->
                        <!-- KDHTODO only add the "old" class if we're on a week prior to the current week (or a year prior to the current year) -->
                        <!-- KDHTODO get the REAL MOV and filter it to add a + or - (or nothing) -- this needs to come from the mov table, which will need a Yii Active Record that is joined to...? team I guess?  But when we join to it, we need to join on yr and week too. -->
                        <div class="pickMov old">+18</div>
                        <!-- KDHTODO add "set by system" to the title when appropriate -->
                        <!-- KDHTODO determine when to use logo-small, logo-medium, or logo-large -->
                        <!-- KDHTODO determine when to use logo-small-inactive, logo-medium-inactive, etc -->
                        <div class="logo logo-small" style="background-position:{{pick.team | teamLogoOffset:'small'}}" title="{{pick.team.longname}}" />
                    </div>
                </td>
                <td ng-repeat="i in range" ng-if="i > user.picks.length">*</td>
            </tr>
        </tbody>
    </table>
</div>