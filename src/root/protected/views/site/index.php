<?php
// KDHTODO handle AJAX errors where user is logged out
// KDHTODO since Yii::app()->user is only updated on login, need to update relevant session information when the user changes their login name or password without having them have to log out and back in again

// KDHTODO as cool as Angular is, keeping the controls to toggle the view so much on the main page is slowing things down.  Make them options that are moved to the profile page


$currentWeek = getCurrentWeek();
?>




<script>
/****************************************************************/
//Things below here are the real app
/****************************************************************/
// KDHTODO move this popover stuff somewhere else
// KDHTODO draw the popups using Yii partials
$(function() {

    var fnHideAll, fnGetTrophyData, fnGetBadgeData;

    fnHideAll = function() {
        $('.winnertrophy-wrapper').add('.user-badge').not(this).popover('hide');
    };

    fnGetTrophyData = function() {
        var $this = $(this),
            data = {
                year:  $this.data('year'),
                pot:   $this.data('pot'),
                place: $this.data('place'),
                money: $this.data('money')
            };
        return data;
    };

    fnGetBadgeData = function() {
        var $this = $(this),
            data = {
                name:         $this.data('name'),
                img:          $this.data('img'),
                type:         $this.data('type'),
                points:       $this.data('points'),
                info:         $this.data('info'),
                year:         $this.data('year'),
                tagline:      $this.data('tagline'),
                description:  $this.data('description'),
                unlockedYear: $this.data('unlockedyear'),
                unlockedUser: $this.data('unlockeduser')
            };
        return data;
    };
    
    // popovers for winner trophies
    $('.winnertrophy-wrapper').on('click', function() {
        fnHideAll.call(this);
    }).popover({
        html: true,
        title: function() {
            var data = fnGetTrophyData.call($(this)),
                content;
            // KDHTODO see if the icon's negative margin still works on other devices
            content = '<div class="icon"><img src="/images/badges/winnerbadge-' + data.pot + data.place + '.png" /></div>';
            content += (data.place == 1 ? 'First' : 'Second') + ' Place';
            return content;
        },
        content: function() {
            var data = fnGetTrophyData.call($(this)),
                content;

            content = '';
            content += '<div class="type-label">Winner Trophy</div>';
            content += '<table class="table table-condensed small popover-table">';
            content += '<tr><td>Year</td><td>' + data.year + '</td></tr>';
            content += '<tr><td>Place</td><td>' + (data.place == 1 ? '1st' : '2nd') + '</td></tr>';
            content += '<tr><td>Pot</td><td>' + globals.getPotName(data.pot) + '</td></tr>';
            content += '<tr><td>Won</td><td>' + globals.dollarFormat(data.money) + '</td></tr>';
            content += '</table>';
            // KDHTODO show the number of power points it's worth
            // KDHTODO also get the record (or week of incorrect, or sum of MOV) for additional detail?
            return content;
        },
        placement: 'auto top'
    });

    // popovers for user badges
    $('.user-badge').on('click', function() {
        fnHideAll.call(this);
    }).popover({
        html: true,
        title: function() {
            var data = fnGetBadgeData.call($(this)),
                content;
            // KDHTODO see if the icon's negative margin still works on other devices
            content = '<div class="icon"><img src="' + data.img + '" /></div>' + data.name;
            return content;
        },
        content: function() {
            var data = fnGetBadgeData.call($(this)),
                content;

            content = '';
            content += '<div class="type-label">User Badge</div>';
            content += (data.tagline && data.name != data.tagline ? '<small><em>' + data.tagline + '</em></small>' : '');
            content += '<table class="table table-condensed small popover-table">';
            content += (data.year ? '<tr><td>Awarded</td><td>' + data.year + '</td></tr>' : '');
            content += (data.info ? '<tr><td>Detail</td><td>' + data.info + '</td></tr>' : '');
            content += '<tr class="separator"><td>Type</td><td>' + data.type + '</td></tr>';
            // KDHTODO turn unlocked user into a link to their profile page
            content += (data.unlockedYear || data.unlockedUser ? '<tr><td>Unlocked</td><td>' + (data.unlockedYear ? data.unlockedYear : '') + (data.unlockedUser ? ' by <a href="#">' + data.unlockedUser + '</a>' : '') + '</td></tr>' : '');
            content += '<tr><td>Power&nbsp;Points</td><td>' + data.points + '</td></tr>';
            content += '<tr><td>Description</td><td>' + data.description + '</td></tr>';
            content += '</table>';
            return content;
        },
        placement: 'auto top'
    });
});


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


<div class="container">
    <h4>Most Recent Talk</h4>
    <?php
    foreach ($talk as $t) {
        $this->renderPartial('//_partials/talk', array('talk'=>$t));
    }
    ?>
</div>
<div class="container-fluid">

    
    <h5>Debug Current Week / Header Week: <?php echo $currentWeek;?> / <?php echo getHeaderWeek();?></h5>
    <div ng-controller="BoardCtrl">
    Debug Order: {{order}}<br />
        <!-- KDHTODO clean up table (center headings, etc) -->
        <div class="table-responsive">
            <table class="picks table table-striped table-bordered">
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
                                    echo '<th><a href="' . Yii::app()->createAbsoluteUrl('admin/showCorrect', array('week'=>$week)) . '"><span class="glyphicon glyphicon-flash"></span></th>';
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
                    <tr ng-repeat="user in board | orderBy:[order,'username']" ng-class="{success: user.id == <?php echo userId()?>}">   <!-- KDHTODO have sort order secondary sort be record before username -->
                        <td>{{$index+1}}</td>
                        <td>
                            <!-- KDHTODO this is normally a PHP function, but make an analogous JS function to get avatars too -->
                            <!-- KDHTODO remove inline styles -->
                            <div style="width:44px;float:left;margin-right:10px;"><img class="avatar" ng-src="{{user | userAvatar}}" /></div>
                            <div>
                                {{user.username}}<br />
                                <!-- KDHTODO format this similar to the old site (extract into directive or something?) -->
                                <!-- KDHTODO add "alt" tags and title attributes -->
                                <?php
                                if (userField('show_badges')) {
                                    ?>
                                    <div ng-repeat="win in user.wins | orderBy:['place','pot','yr']" class="winnertrophy-wrapper" data-pot="{{win.pot}}" data-place="{{win.place}}" data-year="{{win.yr}}" data-money="{{win.winnings}}">
                                        <!-- KDHTODO make badges clickable to show modal or go to a link? -->
                                        <!-- KDHTODO after bootstrap is all up and running, adjust style so year overlays are more readable -->
                                        <img ng-src="/images/badges/winnerbadge-{{win.pot}}{{win.place}}.png" />
                                        <div class="year pot{{win.pot}} place{{win.place}}">{{win.yr | shortenYear}}</div>
                                    </div>
                                    <img class="user-badge" ng-repeat="userBadge in user.userBadges | orderBy:'badge.zindex'" ng-src="{{userBadge.badge.img}}" alt="{{userBadge.badge.zindex}}" data-info="{{userBadge.display}}" data-name="{{userBadge.badge.name}}" data-year="{{userBadge.yr}}" data-tagline="{{userBadge.badge.display}}" data-description="{{userBadge.badge.description}}" data-unlockedYear="{{userBadge.badge.unlocked_year}}" data-unlockedUser="{{userBadge.badge.unlockedBy.username}}" data-img="{{userBadge.badge.img}}" data-type="{{userBadge.badge.type}}" data-points="{{userBadge.badge.power_points}}" />
                                    <?php
                                }
                                ?>
                            </div>
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
    </div>
</div>