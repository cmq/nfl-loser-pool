<?php
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
    <li<?php echo (count($classes) ? ' class="' . implode(' ', $classes) . '"' : '')?>><?php echo CHtml::link($name, ($isDisabled ? '#' : $link), ($isExternal ? array('target'=>'_blank') : null))?></li>
    <?
    return ob_get_clean();
}
?>


<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
    <img src="/images/loser-logo-small.png" id="navbar-logo"<?php echo (isGuest() ? ' style="visibility:hidden;"' : '');?> />
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#actual-nav-items">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="<?php echo Yii::app()->createAbsoluteUrl('site/index');?>"><?php echo getCurrentYear()?> NFL Loser Pool</a>
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
                if (!$isGuest) {
                    ?>
                    <li class="dropdown<?php echo ($controllerName == 'stats' ? ' active' : '')?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Statistics <b class="caret"></b></a>
                        <ul class="dropdown-menu">
                            <?php
                            echo navItem('Player Profiles', 'stats/profiles', null, $controllerName == 'stats' && ($actionName == 'index' || $actionName == 'profiles' || $actionName == 'profile'), !$isGuest, !$isPaid);
                            echo navItem('Pick Statistics', 'stats/picks', null, $controllerName == 'stats' && $actionName == 'picks', !$isGuest, !$isPaid);
                            ?>
                        </ul>
                    </li>
                    <li class="dropdown<?php echo ($controllerName == 'archive' ? ' active' : '')?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Archive <b class="caret"></b></a>
                        <ul class="dropdown-menu">
                            <?php
                            echo navItem('Previous Winners', 'archive/winners', null, $controllerName == 'profile' && ($actionName == 'index' || $actionName == 'winners'), !$isGuest, !$isPaid);
                            ?>
                            <li class="divider"></li>
                            <li><a href="#">Past Season Results</a></li>
                            <?php
                            $onYear = (int) getRequestParameter('y', 0);
                            for ($y=param('earliestYear'); $y<getCurrentYear(); $y++) {
                                echo navItem($y, 'archive/year', array('y'=>$y), $controllerName == 'archive' && $actionName == 'year' && $onYear == $y, !$isGuest, !$isPaid, true);
                            }
                            ?>
                        </ul>
                    </li>
                    <?php
                    echo navItem('Settings', 'profile/index', null, $controllerName == 'profile', !$isGuest, !$isPaid);
                }
                ?>
                <li class="dropdown<?php echo ($controllerName == 'about' ? ' active' : '')?>">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">About <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <?php
                        echo navItem('Overview', 'about/overview', null, $controllerName == 'about' && ($actionName == 'index' || $actionName == 'overview'));
                        echo navItem('Site Map', 'about/map', null, $controllerName == 'about' && $actionName == 'map', !$isGuest);
                        echo navItem('History', 'about/history', null, $controllerName == 'about' && $actionName == 'history');
                        echo navItem('Rules', 'about/rules', null, $controllerName == 'about' && $actionName == 'rules');
                        echo navItem('Payout', 'about/payout', null, $controllerName == 'about' && $actionName == 'payout');
                        echo navItem('Trophies/Badges', 'about/badges', null, $controllerName == 'about' && $actionName == 'badges');
                        echo navItem('Power Ranking', 'about/power', null, $controllerName == 'about' && $actionName == 'power');
                        echo navItem('Bandwagon', 'about/bandwagon', null, $controllerName == 'about' && $actionName == 'bandwagon');
                        echo navItem('Technical Info', 'about/tech', null, $controllerName == 'about' && $actionName == 'tech');
                        ?>
                    </ul>
                </li>
                <?php
                echo navItem('NFL Schedule', 'http://www.nfl.com/schedules/' . getCurrentYear() . (getHeaderWeek() <= 17 ? '/REG/' . max(getHeaderWeek(), 1) : '/POST' . (getHeaderWeek()-17)), null, false, true, false, false, true);
                ?>
                <li class="hidden-md hidden-lg"><?php echo (isGuest() ? CHtml::link('Login', array('site/login')) : CHtml::link('Logout', array('site/logout')));?></li>
            </ul>
            <p class="hidden-xs hidden-sm navbar-text navbar-right loginlogout">
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
