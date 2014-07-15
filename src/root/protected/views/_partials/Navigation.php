<?php
// KDHTODO what does navigation look like for a guest?

// KDHTODO nav links that still need to be added and written:
// KDHTODO About page
// KDHTODO NFL Schedule
// KDHTODO Stat Rankings page
// KDHTODO Pick Stats page
// KDHTODO Prior Winners page
// KDHTODO Season Archive page

$controllerName = $this->uniqueid;
$actionName     = $this->action->id;

function navItem($name, $link, $params=null, $isActive=false, $isVisible=true, $isDisabled=false, $isSmall=false, $isExternal=false) {
    $classes = array();
    if (!$isVisible) {
        return '';
    }
    if ($isActive) {
        $classes[] = 'active';
    }
    if ($isDisabled) {
        $classes[] = 'disabled';
    }
    if ($isSmall) {
        $classes[] = 'small';
        $classes[] = 'navbar-left';
    }
    if ($isExternal) {
        $name .= ' <span class="glyphicon glyphicon-share-alt"></span>';
    } else {
        $link = array($link);
        if (is_array($params)) {
            $link = array_merge($link, $params);
        }
    }
    ob_start();
    ?>
    <li<?php echo (count($classes) ? ' class="' . implode(' ', $classes) . '"' : '')?>><?php echo CHtml::link($name, $link, ($isExternal ? array('target'=>'_blank') : null))?></li>
    <?
    return ob_get_clean();
}
?>


<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
    <img src="/images/loser-logo-small.png" id="navbar-logo" />
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#actual-nav-items">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#"><?php echo getCurrentYear()?> NFL Loser Pool</a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="actual-nav-items">
            <ul class="nav navbar-nav">
                <?php
                $isGuest = isGuest();
                $isPaid  = isPaid();
                echo navItem('Home', 'site/index', null, $controllerName == 'site');
                echo navItem('Make Picks', 'pick/index', null, $controllerName == 'pick', !$isGuest);
                echo navItem('Messages', 'talk/index', null, $controllerName == 'talk', !$isGuest, !$isPaid);
                ?>
                <li class="dropdown<?php echo ($controllerName == 'stats' ? ' active' : '')?>">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Statistics <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <?php
                        echo navItem('Player Profiles', 'stats/profiles', null, $controllerName == 'stats' && ($actionName == 'index' || $actionName == 'profiles' || $actionName == 'profile'));
                        echo navItem('Pick Statistics', 'stats/picks', null, $controllerName == 'stats' && $actionName == 'picks');
                        ?>
                    </ul>
                </li>
                <li class="dropdown<?php echo ($controllerName == 'archive' ? ' active' : '')?>">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Archive <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <?php
                        echo navItem('Previous Winners', 'archive/winners', null, $controllerName == 'profile' && ($actionName == 'index' || $actionName == 'winners'));
                        ?>
                        <li class="divider"></li>
                        <li><a href="#">Past Season Results</a></li>
                        <?php
                        $onYear = (int) getRequestParameter('y', 0);
                        for ($y=param('earliestYear'); $y<getCurrentYear(); $y++) {
                            echo navItem($y, 'archive/year', array('y'=>$y), $controllerName == 'archive' && $actionName == 'year' && $onYear == $y, true, false, true);
                        }
                        ?>
                    </ul>
                </li>
                <?php
                echo navItem('Settings', 'profile/index', null, $controllerName == 'profile', !$isGuest, !$isPaid);
                echo navItem('About', 'about/index', null, $controllerName == 'about');
                echo navItem('NFL Schedule', 'http://www.nfl.com/schedules/' . getCurrentYear() . (getHeaderWeek() <= 17 ? '/REG/' . max(getHeaderWeek(), 1) : '/POST' . (getHeaderWeek()-17)), null, false, true, false, false, true);
                ?>
            </ul>
            <p class="navbar-text navbar-right loginlogout">
                Welcome, <?php echo (isGuest() ? 'Guest' : getProfileLink(user()));?>
                <small>(<?php echo (isGuest() ? CHtml::link('Login', array('site/login')) : CHtml::link('Logout', array('site/logout')));?>)</small>
            </p>
            <?php
            if (isAdmin()) {
                ?>
                <ul class="nav navbar-nav navbar-right">
                    <li class="dropdown<?php echo ($controllerName == 'admin' ? ' active' : '')?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Admin <b class="caret"></b></a>
                        <ul class="dropdown-menu">
                            <?php
                            echo navItem('Correct Picks', 'admin/showCorrect', null, $controllerName == 'admin' && $actionName == 'showCorrect', isAdmin());
                            echo navItem('Create Account', 'admin/newAccount', null, $controllerName == 'admin' && $actionName == 'newAccount', isSuperadmin());
                            echo navItem('Recalculate Rankings', 'admin/recalculateRankings', null, $controllerName == 'admin' && $actionName == 'recalculateRankings', isSuperadmin());
                            ?>
                        </ul>
                    </li>
                </ul>
                <?
            }
            ?>
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>                
