<?php
function isAdmin() {
    return (bool) userField('admin');
}

function isSuperadmin() {
    return (bool) userField('superadmin');
}

function getCurrentYear() {
    return param('currentYear');
}

function getCurrentWeek() {
    $currentWeek = param('currentWeek');
    if ($currentWeek) {
        return $currentWeek;
    }
    // KDHTODO remove the following line -- it is in place only for developing v2
    return 19;
    
    $dNow        = time();
    $firstGames  = param('firstGame');
    $currentWeek = 0;
    for ($i=1; $i<=21; $i++) {
    	if ($dNow > ($firstGames[$i]-(60*60))) {
    		// "now" is greater than the first game of week $i minus an hour (60*60 seconds)
    		$currentWeek = $i;
    	}
    }
    return $currentWeek;
}

function getHeaderWeek() {
    $headerWeek = param('currentWeek');
    if ($headerWeek) {
        return $headerWeek;
    }
    
    $dNow       = time();
    $firstGames = param('firstGame');
    $headerWeek = 0;
    for ($i=1; $i<=21; $i++) {
    	if ($dNow > ($firstGames[$i]-(60*60*24*5))) {
    	    // the first game of week $i is within 5 days
    		$headerWeek = $i;
    	} else if ($i > 20 && $dNow > ($firstGames[$i]-(60*60*24*12))) {
    		// otherwise, a special case for the superbowl week... if we're within 12 days
    		$headerWeek = $i;
    	}
    }
    $headerWeek = min(max(1, $headerWeek), 21);
    if ($headerWeek < 18 && date('w', $firstGames[$headerWeek]) > 1) {
    	// the first game of the header week is on a Tuesday-Saturday (i.e., not Sunday) -- if the current day is not yet Tuesday,
    	// continue to show the previous week in the header, because even though we're within 5 days of the next week's games starting,
    	// the current week is still going on!
    	// Note that this doesn't apply during the playoffs
        if (date('w', $dNow) < 2) {
    		$headerWeek--;
    	}
    }
    return $headerWeek;
}

function getWeekName($week) {
    switch ($week) {
        case 21:
            return 'Superbowl';
            break;
        case 20:
            return 'Conf Champ';
            break;
        case 19:
            return 'Divisional';
            break;
        case 18:
            return 'Wild Card';
            break;
        default:
            return $week;
            break;
    }
}

function getLockTime($week, $format=false) {
    // KDHTODO get real lock time
    $locktime = new DateTime();
    // KDHTODO adjust format
    return $format ? $locktime->format('m/d/y h:i a') : $locktime;
}