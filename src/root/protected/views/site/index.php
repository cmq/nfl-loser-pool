<!-- KDHTODO are any of these JS objects even necessary now that we're using angular? -->
<script src="<?php echo baseUrl('/js/model/Badge.js'); ?>"></script>
<script src="<?php echo baseUrl('/js/model/Pick.js'); ?>"></script>
<script src="<?php echo baseUrl('/js/model/Team.js'); ?>"></script>
<script src="<?php echo baseUrl('/js/model/User.js'); ?>"></script>
<script src="<?php echo baseUrl('/js/model/UserYear.js'); ?>"></script>
<?php
// KDHTODO handle AJAX errors where user is logged out
// KDHTODO since Yii::app()->user is only updated on login, need to update relevant session information when the user changes their login name or password without having them have to log out and back in again

// KDHTODO as cool as Angular is, keeping the controls to toggle the view so much on the main page is slowing things down.  Make them options that are moved to the profile page
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


$currentWeek = getCurrentWeek();
?>




<script>
/****************************************************************/
//Things below here are the real app
/****************************************************************/
loserpool.controller('BoardCtrl', ['$scope', function($scope) {
    $scope.order        = 'username';
    $scope.board        = <?php echo CJSON::encode($boardData);?>;
    $scope.currentWeek  = <?php echo $currentWeek; ?>;
    $scope.currentYear  = <?php echo getCurrentYear(); ?>;
    $scope.viewOptions  = {
        collapseHistory: <?php echo userField('collapse_history') ? 'true' : 'false'?>,
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
        suffix += pick.week == <?php echo $currentWeek;?> ? 'medium' : 'small';
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
loserpool.filter('userAvatar', function() {
    return function(user) {
        var img = 't' + user.id + '.' + user.avatar_ext;
        if (!user.avatar_ext) {
            img = 't0.png';
        }
        return '<?php echo param('avatarWebDirectory')?>/' + img;
    };
});

</script>


<h4>Most Recent Talk</h4>
<?php
foreach ($talk as $t) {
    $this->renderPartial('//_partials/talk', array('talk'=>$t));
}
?>

<h5>Debug Current Week / Header Week: <?php echo $currentWeek;?> / <?php echo getHeaderWeek();?></h5>
<div ng-controller="BoardCtrl">
Debug Order: {{order}}<br />
    <table border="1">
        <thead>
            <tr>
                <th>&nbsp;</th>
                <!-- KDHTODO add support for reversing the sort order (should work by simply prefixing the sort properties with a minus sign) -->
                <th ng-click="order = 'username'">User</th>
                <?php
                if (userField('collapse_history') && $currentWeek > 2) {
                    echo '<th>Weeks 1 - ' . ($currentWeek-1) . '</th>';
                }
                for ($week=1; $week<=21; $week++) {
                    if (!userField('collapse_history') || $week >= $currentWeek) {
                        ?><th ng-click="setOrder(<?=$week?>-1)"><?=getWeekName($week)?></th><?php
                    }
                }
                ?>
                <!-- KDHTODO instead of old way, where there tries to be 1 column for who's ahead, let there be a column for every pot, and a final column for total money -->
            </tr>
            <?php
            if (isAdmin()) {
                ?>
                <tr>
                    <th colspan="2"/>
                    <?php
                    if (userField('collapse_history') && $currentWeek > 2) {
                        echo '<th></th>';
                    }
                    for ($week=1; $week<=21; $week++) {
                        if (!userField('collapse_history') || $week >= $currentWeek) {
                            // KDHTODO make font much smaller so it doesn't make columns wider
                            echo '<th><a href="' . Yii::app()->createAbsoluteUrl('admin/showCorrect', array('week'=>$week)) . '">Cor</th>';
                        }
                    }
                    ?>
                    </tr>
                <?php
            }
            ?>
        </thead>
        <tbody>
            <!-- KDHTODO highlight the current user's row -->
            <!-- all of these ng-repeats may be extraneous... ng-repeat is only useful for data binding, but for static data that won't change, we're just slowing things down on the client side -->
            <!-- on second thought, without the ng-repeat data binding, we couldn't dynamically sort on the client-side (without a plugin or something) -->
            <tr ng-repeat="user in board | orderBy:[order,'username']">   <!-- KDHTODO have sort order secondary sort be record before username -->
                <td>{{$index+1}}</td>
                <td>
                    <!-- KDHTODO this is normally a PHP function, but make an analogous JS function to get avatars too -->
                    <div style="width:44px;float:left;"><img src="{{user | userAvatar}}" /></div>
                    {{user.username}}
                    <!-- KDHTODO format this similar to the old site (extract into directive or something?) -->
                    <!-- KDHTODO add "alt" tags and title attributes -->
                    <?php
                    if (userField('show_badges')) {
                        ?>
                        <div ng-repeat="win in user.wins | orderBy:['place','pot','yr']" class="winnerbadge-wrapper">
                            <!-- KDHTODO make badges clickable to show modal or go to a link? -->
                            <!-- KDHTODO after bootstrap is all up and running, adjust style so year overlays are more readable -->
                            <img ng-src="/images/badges/winnerbadge-{{win.pot}}{{win.place}}.png" />
                            <div class="year pot{{win.pot}}">{{win.yr | shortenYear}}</div>
                        </div>
                        <!-- KDHTODO change alt text to something real (not the zindex) -->
                        <img ng-repeat="userBadge in user.userBadges | orderBy:'badge.zindex'" ng-src="{{userBadge.badge.img}}" alt="{{userBadge.badge.zindex}}" title="{{userBadge.badge.zindex}}" />
                        <?php
                    }
                    ?>
                </td>
                <td ng-if="viewOptions.collapseHistory" align="center">{{getOldRecord(user)}}</td>
                <td ng-repeat="pick in user.picks" ng-if="pick.week >= currentWeek || !viewOptions.collapseHistory" ng-class="{incorrect: pick.incorrect}" align="center">
                    <div style="position:relative;">    <!-- KDHTODO add a mov-wrapper class instead of an inline style -->
                        <?php
                        if (userField('show_mov')) {
                            ?><div class="pickMov" ng-class="{old: pick.week < currentWeek || pick.year < currentYear, incorrect: pick.incorrect}">{{pick.mov.mov | stylizeMov}}</div><?php
                        }
                        ?>
                        <!-- KDHTODO add "set by system" to the title when appropriate -->
                        <!-- KDHTODO determine when to use logo-small-inactive, logo-medium-inactive, etc -->
                        
                        <!-- KDHTODO the filter for image offset is a fixed 50ximage_offset, but we can't supply a filter for a parameter of another filter (that I've seen).  However, since we know the constant is there an easier way to do this? -->
                        <?php
                        if (userField('show_logos')) {
                            ?><div class="logo logo-{{pick | teamLogo}}" style="background-position:{{pick.team | teamLogoOffset:'small'}}" title="{{pick.team.longname}}"></div><?php
                        } else {
                            ?><span>{{pick.team.shortname}}</span><?php
                        }
                        ?>
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