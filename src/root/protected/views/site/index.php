<!-- KDHTODO are any of these JS objects even necessary now that we're using angular? -->
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

<h2>You are logged in as <?php echo ($user->isGuest ? 'guest' : 'registered user ' . userId() . ' (' . userField('username') . ' - ' . userField('firstname') . ' ' . userField('lastname') . ')')?></h2>
<?php
if (Yii::app()->user->isGuest) {
    // KDHTODO how to handle what guests can see?
    ?>You are a guest.  What does that mean you can access?  Past seasons, perhaps?  But you may want to <a href="<?php echo $this->createUrl('site/login')?>">log in</a><br /><?php
} else {
    ?><a href="<?php echo $this->createUrl('site/logout')?>">Logout</a><br /><?php
}

?>




<script>
/****************************************************************/
//Things below here are the real app
/****************************************************************/
loserpool.controller('BoardCtrl', ['$scope', function($scope) {
    $scope.order        = 'username';
    $scope.board        = <?php echo CJSON::encode($boardData);?>;
    $scope.currentWeek  = <?php echo getCurrentWeek(); ?>;
    $scope.currentYear  = <?php echo getCurrentYear(); ?>;
    $scope.viewOptions  = {
        // KDHTODO get these from server
        hideOld:    false, // KDHTODO only allow this to be true if the current week is at least 3 (otherwise it makes no sense)
        hideBadges: false,
        hideLogos:  false,
        hideMov:    false
    };
    console.dir($scope.board);  // KDHTODO remove

    $scope.setOrder = function(order) {
        $scope.order = 'picks[' + order + '].team.shortname';
    };
    $scope.getOldRecord = function(user) {
        var i, wins = losses = 0;
        for (i=0; i<user.picks.length; i++) {
            if (user.picks[i].week < $scope.currentWeek) {
                wins   += user.picks[i].incorrect ? 0 : 1;
                losses += user.picks[i].incorrect ? 1 : 0;
            }
        }
        return wins + '-' + losses;
    };
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
        var offset     = (team && team.hasOwnProperty('image_offset') ? parseInt(team.image_offset, 10) : 0);
        if (size.toLowerCase == 'large') {
            multiplier = 80;
        }
        return '0 -' + (multiplier * offset) + 'px';
    };
});
loserpool.filter('teamLogo', function() {
    return function(pick) {
        var suffix = '';
        suffix += pick.week == <?php echo getCurrentWeek();?> ? 'medium' : 'small';
        suffix += pick.setbysystem ? '-inactive' : '';
        return suffix;
    };
});
loserpool.filter('stylizeMov', function() {
    return function (mov) {
        mov = parseInt(mov, 10) * -1;
        if (isNaN(mov)) {
            return '';
        }
        if (mov > 0) {
            return '+' + mov;
        }
        return mov;
    };
});
</script>

<h5>Debug Current Week / Header Week: <?php echo getCurrentWeek();?> / <?php echo getHeaderWeek();?></h5>
<div ng-controller="BoardCtrl">
Debug Order: {{order}}<br />
    <button ng-click="viewOptions.hideOld = !viewOptions.hideOld">Toggle Collapsed History</button>
    <button ng-click="viewOptions.hideBadges = !viewOptions.hideBadges">Toggle Badges</button>
    <button ng-click="viewOptions.hideLogos = !viewOptions.hideLogos">Toggle Logos</button>
    <button ng-click="viewOptions.hideMov = !viewOptions.hideMov">Toggle MOV</button>
    <table border="1">
        <thead>
            <tr>
                <th>&nbsp;</th>
                <!-- KDHTODO add support for reversing the sort order (should work by simply prefixing the sort properties with a minus sign) -->
                <th ng-click="order = 'username'">User</th>
                <th ng-if="viewOptions.hideOld">Weeks 1 - {{currentWeek-1}}</th>
                <?php
                for ($week=1; $week<=21; $week++) {
                    ?><th ng-if="currentWeek <= <?=$week?> || !viewOptions.hideOld" ng-click="setOrder(<?=$week?>-1)"><?=getWeekName($week)?></th><?php
                }
                ?>
                <!-- KDHTODO instead of old way, where there tries to be 1 column for who's ahead, let there be a column for every pot, and a final column for total money -->
            </tr>
        </thead>
        <tbody>
            <!-- KDHTODO highlight the current user's row -->
            <!-- KDHTODO all of these ng-repeats may be extraneous... ng-repeat is only useful for data binding, but for static data that won't change, we're just slowing things down on the client side -->
            <tr ng-repeat="user in board | orderBy:[order,'username']">   <!-- KDHTODO have sort order secondary sort be record before username -->
                <td>{{$index+1}}</td>
                <td>
                    {{user.username}}
                    <!-- KDHTODO format this similar to the old site (extract into directive or something?) -->
                    <!-- KDHTODO add "alt" tags and title attributes -->
                    <div ng-repeat="win in user.wins | orderBy:['place','pot','yr']" class="winnerbadge-wrapper" ng-class="{hidden: viewOptions.hideBadges}">
                        <!-- KDHTODO make badges clickable to show modal or go to a link? -->
                        <!-- KDHTODO after bootstrap is all up and running, adjust style so year overlays are more readable -->
                        <img ng-src="/images/badges/winnerbadge-{{win.pot}}{{win.place}}.png" />
                        <div class="year pot{{win.pot}}">{{win.yr | shortenYear}}</div>
                    </div>
                    <!-- KDHTODO change alt text to something real (not the zindex) -->
                    <img ng-repeat="userBadge in user.userBadges | orderBy:'badge.zindex'" ng-class="{hidden: viewOptions.hideBadges}" ng-src="{{userBadge.badge.img}}" alt="{{userBadge.badge.zindex}}" title="{{userBadge.badge.zindex}}" />
                </td>
                <td ng-if="viewOptions.hideOld" align="center">{{getOldRecord(user)}}</td>
                <td ng-repeat="pick in user.picks" ng-if="pick.week >= currentWeek || !viewOptions.hideOld" ng-class="{incorrect: pick.incorrect}" align="center">
                    <div style="position:relative;">    <!-- KDHTODO add a mov-wrapper class instead of an inline style -->
                        <!-- note:  This operates faster by adding a class of hidden bound to viewOptions.hideMov than it does using ng-if bound to the same thing -->
                        <div class="pickMov" ng-class="{hidden: viewOptions.hideMov, old: pick.week < currentWeek || pick.year < currentYear, incorrect: pick.incorrect}">{{pick.mov.mov | stylizeMov}}</div>
                        <!-- KDHTODO add "set by system" to the title when appropriate -->
                        <!-- KDHTODO determine when to use logo-small-inactive, logo-medium-inactive, etc -->
                        
                        <!-- KDHTODO the filter for image offset is a fixed 50ximage_offset, but we can't supply a filter for a parameter of another filter (that I've seen).  However, since we know the constant is there an easier way to do this? -->
                        <div class="logo logo-{{pick | teamLogo}}" ng-class="{hidden: viewOptions.hideLogos}" style="background-position:{{pick.team | teamLogoOffset:'small'}}" title="{{pick.team.longname}}"></div>
                        <span ng-class="{hidden: !viewOptions.hideLogos}">{{pick.team.shortname}}</span>
                    </div>
                </td>
                <?php
                for ($week=1; $week<=21; $week++) {
                    ?><td ng-if="user.picks.length < <?=$week?>">&nbsp;</td><?php
                }
                ?>
            </tr>
        </tbody>
    </table>
</div>