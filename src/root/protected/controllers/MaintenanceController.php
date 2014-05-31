<?php
// KDHTODO clean up this file so these random functions aren't hanging around all over cluttering things up
// KDHTODO make this page have a layout so the navigation is still present, etc.
// KDHTODO show output on this screen for timing and whatnot
// KDHTODO the week-by-week breakdown of power ranking is pointless unless we also want to calculate floating badges week-by-week (sort of the same with talks and likes to a lesser extent)

/*
<ul>
    <li>Record per Year (show as bar graph?)</li>
    <li>Times Dodging a Bandwagon Crash</li>
    <li>Number of Times Picking Each Team</li>
    <li>Current Power Ranking</li>
    <li>Highest Power Ranking</li>
    <li>Lowest Power Ranking</li>
    <li>Largest One-Week Power Ranking Jump</li>
    <li>Power-Ranking Calculation Details</li>
    <li>Year-by-year Stat Breakdown?</li>
    <li>Floating or losable badges owned at one point (and which points)</li>
</ul>
*/
class MaintenanceController extends Controller
{
    
    private $reverseStats = null;
    private $users        = array();
    private $bandwagons   = array();
    private $floatingBadges = array();
    
    
    // map of stat.id to the key we'll use to describe/calculate that stat
    private $STATS = array(
        1  => 'numSeasons',
        2  => 'entryFee',
        3  => 'money',
        4  => 'roi',
        5  => 'pickstotal',
        6  => 'picksmanual',
        7  => 'pickssetbysystem',
        8  => 'pickscorrect',
        9  => 'picksincorrect',
        10 => 'picksmanualcorrect',
        11 => 'picksmanualincorrect',
        12 => 'pickssetbysystemcorrect',
        13 => 'pickssetbysystemincorrect',
        14 => 'percentmanual',
        15 => 'percentsetbysystem',
        16 => 'percentcorrect',
        17 => 'percentincorrect',
        18 => 'percentmanualcorrect',
        19 => 'percentmanualincorrect',
        20 => 'percentsetbysystemcorrect',
        21 => 'percentsetbysystemincorrect',
        22 => 'powerrank',
        23 => 'totalMargin',
        24 => 'averageMargin',
        25 => 'streakcorrect',
        26 => 'streakincorrect',
        27 => 'averageCorrect',
        28 => 'averageIncorrect',
        29 => 'averageWeekIncorrect',
        30 => 'currentStreak',
        31 => 'postsBy',
        32 => 'postsAt',
        33 => 'likesBy',
        34 => 'likesAt',
        35 => 'referrals',
        36 => 'firstPlace',
        37 => 'secondPlace',
        38 => 'numTrophies',
        39 => 'numBadges',
        40 => 'numBandwagons',
        41 => 'percentBandwagons',
        42 => 'bandwagonChief',
        43 => 'percentChief',
        44 => 'bandwagonJumper',
    );
    

    private function _isReverseStat($key) {
        if (is_null($this->reverseStats)) {
            $this->reverseStats = array();
            $sql = 'select * from stat where reverse = 1';
            $rsStat = Yii::app()->db->createCommand($sql)->query();
            foreach ($rsStat as $row) {
                $this->reverseStats[] = $row['id'];
            }
        }
        return array_search(array_search($key, $this->STATS), $this->reverseStats) !== false;
    }
    
    private function _getPreviousWeek($user, $endYear, $endWeek) {
        $previous = null;
        foreach ($user['years'] as $y=>$year) {
            foreach ($year['weeks'] as $w=>$week) {
                if ($y > $endYear || ($y == $endYear && $w >= $endWeek)) {
                    break;
                } else {
                    $previous = array('y'=>$y, 'w'=>$w);
                }
            }
        }
        return $previous;
    }
    
    private function _getHighestRankedUser($users) {
        $bestPowerRank = 99999;
        $highestUser   = null;
        foreach ($users as $user) {
            if ($user['powerrank'] < $bestPowerRank) {
                $bestPowerRank = $user['powerrank'];
                $highestUser   = $user;
            }
        }
        return $highestUser;
    }
    
    private function _userHasPick($user, $year, $week, $teams, $searchPending=false) {
        if (!is_array($teams)) {
            $teams = array($teams);
        }
        if (array_key_exists($year, $user['years']) &&
            array_key_exists($week, $user['years'][$year]['weeks']) &&
            array_search($user['years'][$year]['weeks'][$week]['teamid'], $teams) !== false) {
            // this user has this year/week and one of the teams in the list
            return true;
        }
        if ($searchPending) {
            if (array_key_exists($year, $user['years']) &&
                array_key_exists($week, $user['years'][$year]['pendingPicks']) &&
                array_search($user['years'][$year]['pendingPicks'][$week], $teams) !== false) {
                // this user has this year/week and one of the teams in the list
                return true;
            }
        }
        return false;
    }
    
    private function _weeksOnBandwagon($user, $toYear, $toWeek) {
        $b = array_reverse($this->bandwagons);
        $weeksOn = 0;
        foreach ($b as $bandwagon) {
            if ($bandwagon['year'] > $toYear || ($bandwagon['year'] == $toYear && $bandwagon['week'] >= $toWeek)) {
                // we haven't traveled backwards far enough yet
                continue;
            }
            if ($this->_userHasPick($user, $bandwagon['year'], $bandwagon['week'], $bandwagon['teamid'], true)) {
                $weeksOn++;
            } else {
                break;
            }
        }
        return $weeksOn;
    }
    
    private function _buildStreaks(&$user) {
        $correct = array(
            'length'    => 0,
            'startYear' => 0,
            'startWeek' => 0,
            'endYear'   => 0,
            'endWeek'   => 0
        );
        $incorrect = array(
            'length'    => 0,
            'startYear' => 0,
            'startWeek' => 0,
            'endYear'   => 0,
            'endWeek'   => 0
        );
        
        // find the longest of each type of streak
        foreach ($user['years'] as $y=>$year) {
            foreach ($year['weeks'] as $w=>$week) {
                if ($week['streak'] > $correct['length']) {
                    $correct['length']  = $week['streak'];
                    $correct['endYear'] = $y;
                    $correct['endWeek'] = $w;
                } else if ($week['streak'] < $incorrect['length']) {
                    $incorrect['length']  = $week['streak'];
                    $incorrect['endYear'] = $y;
                    $incorrect['endWeek'] = $w;
                }
            }
        }
        
        // find when the longest streaks started
        if ($correct['length'] !== 0) {
            $done = false;
            $correct['startWeek'] = $correct['endWeek'];
            $correct['startYear'] = $correct['endYear'];
            while (!$done) {
                $previousWeek = $this->_getPreviousWeek($user, $correct['startYear'], $correct['startWeek']);
                if ($previousWeek) {
                    if ($user['years'][$previousWeek['y']]['weeks'][$previousWeek['w']]['streak'] === -1) {
                        $done = true;
                    } else {
                        $correct['startYear'] = $previousWeek['y'];
                        $correct['startWeek'] = $previousWeek['w'];
                    }
                } else {
                    $done = true;
                }
            }
        }
        if ($incorrect['length'] !== 0) {
            $done = false;
            $incorrect['startWeek'] = $incorrect['endWeek'];
            $incorrect['startYear'] = $incorrect['endYear'];
            while (!$done) {
                $previousWeek = $this->_getPreviousWeek($user, $incorrect['startYear'], $incorrect['startWeek']);
                if ($previousWeek) {
                    if ($user['years'][$previousWeek['y']]['weeks'][$previousWeek['w']]['streak'] === -1) {
                        $done = true;
                    } else {
                        $incorrect['startYear'] = $previousWeek['y'];
                        $incorrect['startWeek'] = $previousWeek['w'];
                    }
                } else {
                    $done = true;
                }
            }
        }
        
        $incorrect['length'] = abs($incorrect['length']);
        
        // save the streaks in the user
        $user['longCorrectStreak']   = $correct;
        $user['longIncorrectStreak'] = $incorrect;
    }
    
    private function _getStatFromUser($user, $key, $y=null) {
        $value      = null;
        $collection = $user;
        $fetchKey   = $key;
        
        // pre-fetch modifications
        switch ($key) {
            case 'pickstotal':
            case 'picksmanual':
            case 'pickssetbysystem':
            case 'pickscorrect':
            case 'picksincorrect':
            case 'picksmanualcorrect':
            case 'picksmanualincorrect':
            case 'pickssetbysystemcorrect':
            case 'pickssetbysystemincorrect':
            case 'percenttotal':
            case 'percentmanual':
            case 'percentsetbysystem':
            case 'percentcorrect':
            case 'percentincorrect':
            case 'percentmanualcorrect':
            case 'percentmanualincorrect':
            case 'percentsetbysystemcorrect':
            case 'percentsetbysystemincorrect':
            case 'totalMargin':
            case 'averageMargin':
                if ($y && array_key_exists($y, $collection['years'])) {
                    $collection = $user['years'][$y]['pickTotals'];
                } else {
                    $collection = $user['pickTotals'];
                }
                $fetchKey = str_replace('average', 'total', str_replace('percent', '', str_replace('picks', '', $key)));
                break;
            case 'streakcorrect':
                return $user['longCorrectStreak']['length'];
                break;
            case 'streakincorrect':
                return $user['longIncorrectStreak']['length'];
                break;
            case 'numBadges':
                return count($user['badges']);
                break;
            case 'percentBandwagons':
                $fetchKey = 'numBandwagons';
                break;
            case 'percentChief':
                $fetchKey = 'bandwagonChief';
                break;
        }
        
        // get the value out of the user array
        if (is_null($y)) {
            if (array_key_exists($fetchKey, $collection)) {
                $value = $collection[$fetchKey];
            }
        } else {
            if (array_key_exists($y, $collection['years']) && array_key_exists($fetchKey, $collection['years'][$y])) {
                $value = $collection['years'][$y][$fetchKey];
            }
        }
        
        // post-fetch modifications
        switch ($key) {
            case 'percenttotal':
            case 'percentmanual':
            case 'percentsetbysystem':
            case 'percentcorrect':
            case 'percentincorrect':
            case 'percentmanualcorrect':
            case 'percentmanualincorrect':
            case 'percentsetbysystemcorrect':
            case 'percentsetbysystemincorrect':
            case 'averageMargin':
            case 'percentBandwagons':
            case 'percentChief':
                $totalPicks = $this->_getStatFromUser($user, 'pickstotal', $y);
                if ($totalPicks) {
                    $value = ((int) $value / $totalPicks);
                } else {
                    $value = 0;
                }
                break;
        }
        
        return $value;
    }
    
    private function _getPlacesAndTies($values, $key, &$places, &$ties) {
        sort($values);
        if (!$this->_isReverseStat($key)) {
            $values = array_reverse($values);
        }
    
        $place = 0;
        $lastPlace = 0;
        $lastValue = null;
        foreach ($values as $value) {
            $place++;
            if ($value !== $lastValue) {
                $places[$place] = $value;
                $lastPlace = $place;
                $lastValue = $value;
            } else {
                $ties[] = $lastPlace;
            }
        }
        $ties = array_unique($ties);
    }
    
    private function _calculatePlaces($key, $y=null) {
        $places     = array();  // keyed on the place, with the value equal to the value in that place
        $placesa    = array();  // keyed on the place, with the value equal to the value in that place -- active users only
        $ties       = array();  // simple array of values representing places in which there's a tie
        $tiesa      = array();  // simple array of values representing places in which there's a tie -- active users only
        $allValues  = array();
        $allValuesa = array();
    
        foreach ($this->users as $user) {
            $val = $this->_getStatFromUser($user, $key, $y);
            if (!is_null($val)) {
                $allValues[] = $val;
                if ($user['active']) {
                    $allValuesa[] = $val;
                }
            }
        }
        
        $this->_getPlacesAndTies($allValues, $key, $places, $ties);
        $this->_getPlacesAndTies($allValuesa, $key, $placesa, $tiesa);
        
        return array(
            'places'       => $places,
            'activePlaces' => $placesa,
            'ties'         => $ties,
            'activeTies'   => $tiesa
        );
    }
    
    private function _pickTotalsArray() {
        return array(
            'total'                => 0,
            'totalMargin'          => 0,
            'manual'               => 0,
            'setbysystem'          => 0,
            'correct'              => 0,
            'incorrect'            => 0,
            'manualcorrect'        => 0,
            'manualincorrect'      => 0,
            'setbysystemcorrect'   => 0,
            'setbysystemincorrect' => 0,
            'teams'                => array(),
        );
    }
    
    private function _updatePickTotals($pick, &$totals) {
        $totals['total']++;
        $totals['totalMargin']          -= (int) $pick['mov'];
        $totals['manual']               += ($pick['setbysystem'] ? 0 : 1);
        $totals['setbysystem']          += ($pick['setbysystem'] ? 1 : 0);
        $totals['correct']              += ($pick['incorrect'] ? 0 : 1);
        $totals['incorrect']            += ($pick['incorrect'] ? 1 : 0);
        $totals['manualcorrect']        += (!$pick['setbysystem'] && !$pick['incorrect'] ? 1 : 0);
        $totals['manualincorrect']      += (!$pick['setbysystem'] && $pick['incorrect'] ? 1 : 0);
        $totals['setbysystemcorrect']   += ($pick['setbysystem'] && !$pick['incorrect'] ? 1 : 0);
        $totals['setbysystemincorrect'] += ($pick['setbysystem'] && $pick['incorrect'] ? 1 : 0);
        if (array_key_exists($pick['teamid'], $totals['teams'])) {
            $totals['teams'][$pick['teamid']]++;
        } else {
            $totals['teams'][$pick['teamid']] = 1;
        }
    }
    
    private function _getEntryFee($y) {
        return $y == param('earliestYear') ? param('firstYearEntryFee') : (param('entryFee') + ($y >= param('movFirstYear') ? param('movFee') : 0));
    }
    
    private function _insertStat($statId) {
        $statKey   = $this->STATS[$statId];
        $placeData = $this->_calculatePlaces($statKey);
        foreach ($this->users as $u=>$user) {
            $userValue   = $this->_getStatFromUser($user, $statKey);
            $place       = array_search($userValue, $placeData['places']);
            $activePlace = array_search($userValue, $placeData['activePlaces']);
            $tied        = array_search($place, $placeData['ties']) !== false ? 1 : 0;
            $activeTied  = array_search($place, $placeData['activeTies']) !== false ? 1 : 0;
            $meta1       = '';
            $meta2       = '';
            if ($statKey == 'streakcorrect' || $statKey == 'streakincorrect') {
                $streakKey = ($statKey == 'streakcorrect' ? 'longCorrectStreak' : 'longIncorrectStreak');
                $meta1 = getWeekName($user[$streakKey]['startWeek'], true) . ', ' . $user[$streakKey]['startYear'];
                $meta2 = getWeekName($user[$streakKey]['endWeek'], true) . ', ' . $user[$streakKey]['endYear'];
            }
            $activePlace = $activePlace === false ? 0 : $activePlace;
            $sql = "insert into userstat (userid, statid, place, placeactive, tied, tiedactive, value, meta1, meta2) values ($u, $statId, $place, $activePlace, $tied, $activeTied, $userValue, '$meta1', '$meta2')";
            $rs = Yii::app()->db->createCommand($sql)->query();
        }
    }
    
    private function _recalcStats($userId=0) {
        $userId = (int) $userId;
        $users = array();
        
        // build the users first with all of their pick data
        $sql = 'select      u.id,
                            u.username,
                            u.active,
                            mov.mov,
                            p.*
        		from user   u
        		inner join  loserpick p on u.id = p.userid
        					and p.teamid > 0
        		left join	mov on p.teamid = mov.teamid
        		            and p.yr = mov.yr
        		            and p.week = mov.week
                where       1 = 1
                            ' . ($userId ? " and u.id = $userId" : '') . '
        		order by    u.id, p.yr, p.week';
        $lastUserId = 0;
        $rsAll = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsAll as $row) {    // there will only be one
            $u = $row['id'];
            $y = $row['yr'];
            $w = $row['week'];
            
            // on a new user?
            if ($u != $lastUserId) {
                if ($lastUserId) {
                    $users[$lastUserId] = $user;
                }
                $user = array(
                    'id'              => $u,
                    'username'        => $row['username'],
                    'active'          => (bool) $row['active'],
                    'firstYear'       => 9999,
                    'lastYear'        => 0,
                    'entryFee'        => 0,
                    'money'           => 0,
                    'firstPlace'      => 0,
                    'secondPlace'     => 0,
                    'postsBy'         => 0,
                    'postsAt'         => 0,
                    'likesBy'         => 0,
                    'likesAt'         => 0,
                    'referrals'       => 0,
                    'currentStreak'   => 0,
                    'numTrophies'     => 0,
                    'numBadges'       => 0,
                    'numBandwagons'   => 0,
                    'bandwagonChief'  => 0,
                    'bandwagonJumper' => 0,
                    'badges'          => array(),
                    'years'           => array(),
                    'pickTotals'      => $this->_pickTotalsArray(),
                );
                $currentStreak = 0;
                $lastUserId    = $u;
                $lastYear      = 0;
            }
            
            // on a new year?
            if ($y != $lastYear) {
                $user['firstYear'] = min($user['firstYear'], $y);
                $user['lastYear']  = max($user['lastYear'],  $y);
                $user['years'][$y] = array(
                    'weeks'           => array(),
                    'pendingPicks'    => array(),    // key = week#, value = teamid
                    'entryFee'        => $this->_getEntryFee($y),
                    'money'           => 0,
                    'firstPlace'      => 0,
                    'secondPlace'     => 0,
                    'postsBy'         => 0,
                    'postsAt'         => 0,
                    'likesBy'         => 0,
                    'likesAt'         => 0,
                    'referrals'       => 0,
                    'numBandwagons'   => 0,
                    'bandwagonChief'  => 0,
                    'bandwagonJumper' => 0,
                    'firstIncorrect'  => 22,
                    'badges'          => array(),
                    'pickTotals'      => $this->_pickTotalsArray(),
                );
                $user['entryFee'] += $user['years'][$y]['entryFee'];
                $lastYear = $y;
            }
            
            // record the pick itself
            if (is_null($row['incorrect'])) {
                // this is a pending pick.  We don't want it recorded as part of the normal set of picks,
                // but we still want to hold onto it because the bandwagon calculations use it
                $user['years'][$y]['pendingPicks'][$w] = $row['teamid'];
            } else {
                // this is a normal pick that we want to record for all normal calculations
                $user['years'][$y]['weeks'][$w] = array(
                    'teamid'      => $row['teamid'],
                    'mov'         => (int)  $row['mov'],
                    'incorrect'   => (bool) $row['incorrect'],
                    'setbysystem' => (bool) $row['setbysystem'],
                    'streak'      => 0,
                    'onBandwagon' => false
                );
                
                // update totals and other counters
                $user['years'][$y]['firstIncorrect'] = ($row['incorrect'] ? min($row['week'], $user['years'][$y]['firstIncorrect']) : $user['years'][$y]['firstIncorrect']);
                $this->_updatePickTotals($row, $user['pickTotals']);
                $this->_updatePickTotals($row, $user['years'][$y]['pickTotals']);
                
                // check for streaks
                if ($currentStreak == 0 || ((bool) $row['incorrect'] === (bool) ($currentStreak < 0))) {
                    // streak continues
                    $currentStreak += ($row['incorrect'] ? -1 : 1);
                } else {
                    // streak reversed
                    $currentStreak = ($row['incorrect'] ? -1 : 1);
                }
                $user['currentStreak'] = $currentStreak;
                if (!is_null($row['incorrect'])) {
                    $user['years'][$y]['weeks'][$w]['streak'] = $currentStreak;
                }
            }
        }
        if ($lastUserId) {
            $users[$lastUserId] = $user;
        }
        
        
        // get winnings info
        $sql = 'select * from winners' . ($userId ? " where userid = $userId" : '');
        $rsMoney = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsMoney as $row) {
            $users[$row['userid']]['numTrophies'] += 1;
            $users[$row['userid']]['money']       += $row['winnings'];
            $users[$row['userid']]['firstPlace']  += $row['place'] == 1 ? 1 : 0;
            $users[$row['userid']]['secondPlace'] += $row['place'] == 2 ? 1 : 0;
            $users[$row['userid']]['years'][$row['yr']]['money']       += $row['winnings'];
            $users[$row['userid']]['years'][$row['yr']]['firstPlace']  += $row['place'] == 1 ? 1 : 0;
            $users[$row['userid']]['years'][$row['yr']]['secondPlace'] += $row['place'] == 2 ? 1 : 0;
        }
        
        
        // get talk/like info
        $sql = 'select * from losertalk where active = 1 and admin = 0' . ($userId ? " and (postedby = $userId or postedat = $userId)" : '');
        $rsTalk = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsTalk as $row) {
            if (array_key_exists($row['postedby'], $users)) {
                $users[$row['postedby']]['postsBy']++;
                $users[$row['postedby']]['years'][$row['yr']]['postsBy']++;
            }
            if (array_key_exists($row['postedat'], $users)) {
                $users[$row['postedat']]['postsAt']++;
                $users[$row['postedat']]['years'][$row['yr']]['postsAt']++;
            }
        }
        $sql = 'select t.postedby, l.userid, l.yr from losertalk t inner join likes l on l.talkid = t.id where l.active = 1' . ($userId ? " and (t.postedby = $userId or l.userid) = $userId" : '');
        $rsLike = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsLike as $row) {
            if (array_key_exists($row['postedby'], $users)) {
                $users[$row['postedby']]['likesBy']++;
                $users[$row['postedby']]['years'][$row['yr']]['likesBy']++;
            }
            if (array_key_exists($row['userid'], $users)) {
                $users[$row['userid']]['likesAt']++;
                $users[$row['userid']]['years'][$row['yr']]['likesAt']++;
            }
        }
        
        
        // number of players referred
        $sql = 'select u.id, u.referrer, min(lu.yr) first_year from user u inner join loseruser lu on lu.userid = u.id where u.referrer is not null group by u.id, u.referrer';
        $rsReferral = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsReferral as $row) {
            if (array_key_exists($row['referrer'], $users)) {
                // find the year that the referrer will get credit for referring this user
                $creditYear = 0;
                foreach ($users[$row['referrer']]['years'] as $y=>$year) {
                    $creditYear = $y;
                    if ($y > $row['first_year']) {
                        break;
                    }
                }
                $users[$row['referrer']]['referrals']++;
                $users[$row['referrer']]['years'][$creditYear]['referrals']++;
            }
        }
        
        
        // calculate stats that are based on counting/grouping
        foreach ($users as &$user) {
            $user['numSeasons'] = count($user['years']);
            $user['roi']        = $user['money'] / $user['entryFee'];
            
            $has2004            = false;
            $countableSeasons   = count($user['years']);
            $countableCorrect   = $user['pickTotals']['correct'];
            $countableIncorrect = $user['pickTotals']['incorrect'];
            $totalWeekIncorrect = 0;
            foreach ($user['years'] as $y=>$year) {
                $totalWeekIncorrect += $year['firstIncorrect'];
            }
            $user['averageWeekIncorrect'] = $totalWeekIncorrect / $countableSeasons;
            if (array_key_exists(2004, $user['years'])) {
                $has2004 = true;
                $countableSeasons--;
                $countableCorrect   -= $user['years'][2004]['pickTotals']['correct'];
                $countableIncorrect -= $user['years'][2004]['pickTotals']['incorrect'];
            }
            if ($countableSeasons) {
                $user['averageCorrect'] = $countableCorrect / $countableSeasons;
                $user['averageIncorrect'] = $countableIncorrect / $countableSeasons;
            } else {
                // the user ONLY played in 2004 -- we will check for these keys on the output page
                $user['averageCorrect'] = -1;
                $user['averageIncorrect'] = 9999;
            }
            
            $this->_buildStreaks($user);
        }


        // set the internal users array for the following functions to use
        $this->users = $users;
        
        // number of badges for each user (also by year)
        $this->_recalcBadges();     // recalculate the floating and losable badges
        
        // recalculate the power ranking
        $this->_recalcPower();

        // recalculate bandwagons and bandwagon badges/stats
        $this->_recalcBandwagon();
        
        // empty out any previous userstat values
        $sql = 'delete from userstat';
        $rs = Yii::app()->db->createCommand($sql)->query();
        
        // insert all the new values
        foreach ($this->STATS as $s=>$stat) {
            $this->_insertStat($s);
        }
    }
    
    private function _calculateBadge_monkey($y) {
        $sql = '
        	select		user.id, count(loserpick.week) numright
        	from		user
        	inner join	loserpick on loserpick.userid = user.id
        				and loserpick.incorrect = 0
        				and loserpick.teamid > 0
                        and loserpick.yr <= ' . $y . '
            inner join  loseruser on loseruser.userid = user.id
                        and loseruser.yr = ' . $y . '
        	where		1 = 1
        				and not exists (
                            select * from winners
                            where winners.userid = user.id
                                and winners.yr <= ' . $y . '
                        )
        	group by	user.id
        	order by	numright desc
        	limit		1
        ';
        $rsMonkey = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsMonkey as $row) {
            return array(
                'userId' => $row['id'],
                'value'  => $row['numright']
            );
        }
        return null;
    }
    
    private function _calculateBadge_onfire($y) {
        $sql = '
        	select		user.id, loserpick.yr, loserpick.week, loserpick.incorrect
        	from		user
        	inner join	loserpick on loserpick.userid = user.id
        				and loserpick.incorrect is not null
        				and loserpick.yr > 2004
                        and loserpick.yr <= ' . $y . '
            inner join  loseruser on loseruser.userid = user.id
                        and loseruser.yr = ' . $y . '
            where		1 = 1
        	order by	user.id, loserpick.yr, loserpick.week';
        $rsStreak = Yii::app()->db->createCommand($sql)->query();
        $streaks = array();
        $highCurrent = 0;
        $highAlltime = 0;
        $lastUserId = 0;
        foreach ($rsStreak as $row) {
            if ($row['id'] != $lastUserId) {
                if ($lastUserId > 0) {
                    $thisStreakData['maxStreak'] = max($thisStreakData['maxStreak'], $thisStreakData['currentStreak']);
                    $highCurrent = max($highCurrent, $thisStreakData['currentStreak']);
                    $streaks[] = $thisStreakData;
                }
                $thisStreakData = array(
                    'userId'        => $row['id'],
                    'currentStreak' => 0,
                    'maxStreak'     => 0
                );
                $lastUserId = $row['id'];
            }
            if ($row['incorrect'] == 1) {
                $thisStreakData['maxStreak'] = max($thisStreakData['maxStreak'], $thisStreakData['currentStreak']);
                $thisStreakData['currentStreak'] = 0;
            } else {
                $thisStreakData['currentStreak']++;
                $highAlltime = max($highAlltime, $thisStreakData['currentStreak']);
            }
        }
        if ($lastUserId > 0) {
            $thisStreakData['maxStreak'] = max($thisStreakData['maxStreak'], $thisStreakData['currentStreak']);
            $highCurrent = max($highCurrent, $thisStreakData['currentStreak']);
            $streaks[] = $thisStreakData;
        }
        $currentUserId = 0;
        foreach ($streaks as $streak) {
            if ($streak['currentStreak'] == $highCurrent) {
                if ($currentUserId > 0) {
                    // two or more users are tied
                    $currentUserId = 0;
                    break;
                } else {
                    $currentUserId = $streak['userId'];
                }
            }
        }
        $alltimeUserId = 0;
        foreach ($streaks as $streak) {
            if ($streak['maxStreak'] == $highAlltime) {
                if ($alltimeUserId > 0) {
                    // two or more users are tied
                    $alltimeUserId = 0;
                    break;
                } else {
                    $alltimeUserId = $streak['userId'];
                }
            }
        }
        return array(
            'currentUserId' => $currentUserId,
            'currentStreak' => $highCurrent,
            'alltimeUserId' => $alltimeUserId,
            'alltimeStreak' => $highAlltime
        );
    }
    
    private function _calculateBadge_defeat($y) {
        $sql = '
        	select		user.id, avg(mov.mov) avgmov, count(loserpick.week) totalPicks
        	from		user
        	inner join	loserpick on loserpick.userid = user.id
        				and loserpick.incorrect is not null
        				and loserpick.teamid > 0
                        and loserpick.yr <= ' . $y . '
            inner join  mov on loserpick.teamid = mov.teamid
        				and loserpick.yr = mov.yr
        				and loserpick.week = mov.week
            inner join  loseruser on loseruser.userid = user.id
                        and loseruser.yr = ' . $y . '
            where		1 = 1
            group by	user.id
        	having      count(loserpick.week) > 20
        	order by	avgmov
        	limit		1
        ';
        $rsDefeat = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsDefeat as $row) {
            return array(
                'userId' => $row['id'],
                'value'  => $row['avgmov'] * -1
            );
        }
    }
    
    private function _calculateBadge_800($y) {
        $sql = '
            select      user.id, user.username, count(loserpick.userid) totalpicks,
                        (select count(*) from loserpick l where l.userid = user.id and l.incorrect=0 and l.teamid > 0) / (select count(*) from loserpick l where l.userid = user.id and l.teamid > 0 and l.incorrect is not null) as pct
            from        user
            inner join  loserpick on loserpick.userid = user.id
                        and loserpick.incorrect is not null
                        and loserpick.yr <= ' . $y . '
            inner join  loseruser on loseruser.userid = user.id
                        and loseruser.yr = ' . $y . '
            group by    user.id, user.username
            having      count(loserpick.userid) >= 100
                        and (select count(*) from loserpick l where l.userid = user.id and l.incorrect=0 and l.teamid > 0) / (select count(*) from loserpick l where l.userid = user.id and l.teamid > 0 and l.incorrect is not null) >= .800';
        $badgeUsers = array();
        $rs800 = Yii::app()->db->createCommand($sql)->query();
        foreach ($rs800 as $row) {
            $badgeUsers[] = $row['id'];
        }
        return $badgeUsers;
    }
    
    private function _recalcBadges() {
        // This method updates badges that need to be calculated on-the-fly, not just using the userbadge table
        // This method also figures out who had each badge at the end of each year
        
        // NOTE:  This method updates the $user['badges'] array and the $user['years'][$y]['badges'] array
        // NOTE:  This does NOT handle the bandwagon-related badges, those need to be handled by the _recalcBandwagon() method
        
        $this->floatingBadges[] = 19;   // chief of the bandwagon (which is calculated in another function)
        
        $this->floatingBadges[] = 12;   // 12 - monkey on my back
        for ($y=param('earliestYear'); $y<=getCurrentYear(); $y++) {
            $badge = $this->_calculateBadge_monkey($y);
            if ($badge) {
                $this->users[$badge['userId']]['years'][$y]['badges'][] = 12;
                // echo "year $y badge 12 belonged to " . $this->users[$badge['userId']]['username'] . " with {$badge['value']}<br />";
                if ($y == getCurrentYear()) {
                    $this->users[$badge['userId']]['badges'][] = 12;
                    $sql = "update userbadge set userid = {$badge['userId']}, display = '{$badge['value']} manual correct picks without cashing.  And counting...' where badgeid = 12";
                    Yii::app()->db->createCommand($sql)->query();
                }
            }
        }
        
        $this->floatingBadges[] = 13;   // 13 - on fire
        $this->floatingBadges[] = 14;   // 14 - all-time on fire
        for ($y=param('earliestYear'); $y<=getCurrentYear(); $y++) {
            $badge = $this->_calculateBadge_onfire($y);
            if ($badge) {
                if ($badge['currentUserId']) {
                    $this->users[$badge['currentUserId']]['years'][$y]['badges'][] = 13;
                    // echo "year $y badge 13 belonged to " . $this->users[$badge['currentUserId']]['username'] . " with {$badge['currentStreak']}<br />";
                    if ($y == getCurrentYear()) {
                        $this->users[$badge['currentUserId']]['badges'][] = 13;
                        $sql = "update userbadge set userid = {$badge['currentUserId']}, display = 'Current streak leader, with {$badge['currentStreak']} consecutive correct picks.' where badgeid = 13";
                        Yii::app()->db->createCommand($sql)->query();
                    }
                }
                if ($badge['alltimeUserId']) {
                    $this->users[$badge['alltimeUserId']]['years'][$y]['badges'][] = 14;
                    // echo "year $y badge 14 belonged to " . $this->users[$badge['alltimeUserId']]['username'] . " with {$badge['alltimeStreak']}<br />";
                    if ($y == getCurrentYear()) {
                        $this->users[$badge['alltimeUserId']]['badges'][] = 14;
                        $sql = "update userbadge set userid = {$badge['alltimeUserId']}, display = 'All-time streak leader, with {$badge['alltimeStreak']} consecutive correct picks.' where badgeid = 14";
                        Yii::app()->db->createCommand($sql)->query();
                    }
                }
            }
        }
        
        $this->floatingBadges[] = 16;   // 16 - total defeat
        for ($y=param('earliestYear'); $y<=getCurrentYear(); $y++) {
            $badge = $this->_calculateBadge_defeat($y);
            if ($badge) {
                $this->users[$badge['userId']]['years'][$y]['badges'][] = 16;
                // echo "year $y badge 16 belonged to " . $this->users[$badge['userId']]['username'] . " with {$badge['value']}<br />";
                if ($y == getCurrentYear()) {
                    $this->users[$badge['userId']]['badges'][] = 16;
                    $sql = "update userbadge set userid = {$badge['userId']}, display = 'Average Margin of Defeat: " . number_format($badge['value'], 2) . " points' where badgeid = 16";
                    Yii::app()->db->createCommand($sql)->query();
                }
            }
        }
        
        $this->floatingBadges[] = 18;   // 18 - .800 club
        for ($y=param('earliestYear'); $y<=getCurrentYear(); $y++) {
            $badgeUsers = $this->_calculateBadge_800($y);
            if (count($badgeUsers)) {
                if ($y == getCurrentYear()) {
                    $sql = 'delete from userbadge where badgeid=18';
                    Yii::app()->db->createCommand($sql)->query();
                }
                foreach ($badgeUsers as $userId) {
                    // echo "year $y badge 18 belonged to " . $this->users[$userId]['username'] . "<br />";
                    $this->users[$userId]['years'][$y]['badges'][] = 18;
                    if ($y == getCurrentYear()) {
                        $this->users[$userId]['badges'][] = 18;
                        $sql = "insert into userbadge (userid, badgeid, display) values ($userId, 18, '.800 Club - Accuracy of at least 80% after at least 100 picks')";
                        Yii::app()->db->createCommand($sql)->query();
                    }
                }
            }
        }
        
        
        // do the rest of the normal badges that are awarded manually
        $sql = 'select ub.userid, b.id, ub.yr from badge b inner join userbadge ub on ub.badgeid = b.id where 1 = 1';
        $rsBadge = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsBadge as $row) {
            if (array_search($row['id'], $this->floatingBadges) === false && array_key_exists($row['userid'], $this->users)) {
                // this is not a floating badge, and we have the user record
                $this->users[$row['userid']]['badges'][] = $row['id'];
                if ($row['yr']) {
                    // the badge was awarded in a specific year
                    $this->users[$row['userid']]['years'][$row['yr']]['badges'][] = $row['id'];
                }
            }
        }
        
    }
    
    private function _recalcBandwagon() {
        
        $sql = 'select * from bandwagon order by yr, week';
        $rsExistingBandwagons = Yii::app()->db->createCommand($sql)->query();
        $existingBandwagons = array();
        foreach ($rsExistingBandwagons as $eb) {
            $existingBandwagons[] = $eb;
        }
        
        // collect new bandwagons for every year/week that hasn't already been calculated (including multiple teams if there are ties)
        $sql = '
            select      loserpick.teamid, loserpick.yr, loserpick.week, count(*) total
            from        loserpick
            where       loserpick.yr > 2004
            group by    teamid, yr, week
            order by    loserpick.yr, loserpick.week, total desc';
        $rsBandwagon = Yii::app()->db->createCommand($sql)->query();
        $newBandwagons = array();
        $lastYear = 0;
        $lastWeek = 0;
        foreach ($rsBandwagon as $row) {
            if ($row['week'] != $lastWeek || $row['yr'] != $lastYear) {
                if ($lastWeek) {
                    $newBandwagons[] = array(
                        'year'  => (int) $lastYear,
                        'week'  => (int) $lastWeek,
                        'picks' => (int) $numPicks,
                        'teams' => $teamCandidates
                    );
                }
                $teamCandidates = array();
                $numPicks = (int) $row['total'];
                $lastWeek = (int) $row['week'];
                $lastYear = (int) $row['yr'];
            }
            if ((int) $row['total'] == $numPicks) {
                // this team tied for most picks, so it's also a candidate
                $teamCandidates[] = (int) $row['teamid'];
            }
        }
        if ($lastWeek) {
            $newBandwagons[] = array(
                'year'  => (int) $lastYear,
                'week'  => (int) $lastWeek,
                'picks' => (int) $numPicks,
                'teams' => $teamCandidates
            );
        }
        
        // resolve ties by going with the team chosen by the user with the highest power ranking
        foreach ($newBandwagons as &$newBandwagon) {
            if (count($newBandwagon['teams']) > 1) {
                // echo "Bandwagon tie on week {$newBandwagon['week']}, {$newBandwagon['year']}<br />";
                // collect all the users that have any of these teams
                $users = array();
                foreach ($this->users as $user) {
                    if ($this->_userHasPick($user, $newBandwagon['year'], $newBandwagon['week'], $newBandwagon['teams'], true)) {
                        // this user has this year/week and one of the teams in the bandwagon
                        $users[] = $user;
                    }
                }
                // find which of these users has the highest power ranking and take their team as the only candidate
                $highestRankedUser = $this->_getHighestRankedUser($users);
                if ($highestRankedUser) {
                    $newBandwagon['teams'] = array($highestRankedUser['years'][$newBandwagon['year']]['weeks'][$newBandwagon['week']]['teamid']);
                }
            }
        }
        
        // figure out the chief of each bandwagon and insert it
        foreach ($newBandwagons as $bandwagon) {
            $teamId          = $bandwagon['teams'][0];
            $chiefId         = 0;
            $chiefCandidates = array();
            
            // get all the candidates (users who selected the bandwagon team for this week)
            foreach ($this->users as $user) {
                if ($this->_userHasPick($user, $bandwagon['year'], $bandwagon['week'], $teamId, true)) {
                    $chiefCandidates[] = $user;
                }
            }
            
            // determine how long each candidate has been on the bandwagon
            $maxTimeOnBandwagon     = 0;
            $revisedChiefCandidates = array();
            foreach ($chiefCandidates as $chief) {
                $timeOnBandwagon = $this->_weeksOnBandwagon($chief, $bandwagon['year'], $bandwagon['week']);
                $maxTimeOnBandwagon = max($maxTimeOnBandwagon, $timeOnBandwagon);
                $revisedChiefCandidates[] = array(
                    'chief'   => $chief,
                    'weeksOn' => $timeOnBandwagon
                );
            }
            
            // revise the chief candidates to only those who have been on it the longest
            $chiefCandidates = array();
            foreach ($revisedChiefCandidates as $candidate) {
                if ($candidate['weeksOn'] == $maxTimeOnBandwagon) {
                    $chiefCandidates[] = $candidate['chief'];
                }
            }
            
            // break any ties by awarding chief to the highest-ranked user
            if (count($chiefCandidates) > 1) {
                // echo "Chief tie on week {$bandwagon['week']}, {$bandwagon['year']}<br />";
                $chief = $this->_getHighestRankedUser($chiefCandidates);
                if ($chief) {
                    $chiefId = $chief['id'];
                }
            } else {
                $chiefId = $chiefCandidates[0]['id'];
            }
            
            // add this new bandwagon to the global array
            $incorrect = null;
            if ($this->_userHasPick($this->users[$chiefId], $bandwagon['year'], $bandwagon['week'], $teamId, false)) {
                $incorrect = $this->users[$chiefId]['years'][$bandwagon['year']]['weeks'][$bandwagon['week']]['incorrect'];
                if (!is_null($incorrect)) {
                    $incorrect = (int) $incorrect;
                }
            }
            $this->bandwagons[] = array(
                'year'      => $bandwagon['year'],
                'week'      => $bandwagon['week'],
                'chiefid'   => $chiefId,
                'teamid'    => $teamId,
                'incorrect' => $incorrect
            );
            
            // insert the bandwagon into the database if necessary
            $insert = true;
            // try to look at all existing bandwagons to see if we already have the exact content already in the database, and therefore have no need to do an insert
            foreach ($existingBandwagons as $eb) {
                $incorrectMatches = false;
                if (is_null($eb['incorrect'])) {
                    $incorrectMatches = is_null($incorrect);
                } else {
                    $incorrectMatches = ((int) $eb['incorrect'] === $incorrect);
                }
                if ($eb['yr'] == $bandwagon['year'] && $eb['week'] == $bandwagon['week'] && $eb['teamid'] == $teamId && $eb['chiefid'] == $chiefId && $incorrectMatches) {
                    // nothing has changed between this bandwagon and the existing one -- no need to insert
                    $insert = false;
                    break;
                }
                if ($eb['yr'] > $bandwagon['year'] || ($eb['yr'] == $bandwagon['year'] && $eb['week'] > $bandwagon['week'])) {
                    // in our loop over the existing bandwagons, we've already passed the one we're considering inserting -- no point in continuing the search, we're done
                    break;
                }
            }
            if ($insert) { 
                $sql = "replace into bandwagon (yr, week, chiefid, teamid, incorrect) values ({$bandwagon['year']}, {$bandwagon['week']}, $chiefId, $teamId, " . (is_null($incorrect) ? 'null' : $incorrect) . ")";
                // echo "$sql<br />";
                Yii::app()->db->createCommand($sql)->query();
            }
        }
        
        // figure out each user that is on each bandwagon
        foreach ($this->bandwagons as $bandwagon) {
            $teamId = $bandwagon['teamid'];
            foreach ($this->users as &$user) {
                if ($this->_userHasPick($user, $bandwagon['year'], $bandwagon['week'], $teamId)) {
                    $user['years'][$bandwagon['year']]['weeks'][$bandwagon['week']]['onBandwagon'] = true;
                    $user['years'][$bandwagon['year']]['numBandwagons']++;
                    $user['numBandwagons']++;
                }
                if ($bandwagon['chiefid'] == $user['id']) {
                    $user['years'][$bandwagon['year']]['bandwagonChief']++;
                    $user['bandwagonChief']++;
                }
            }
            // set chief of the bandwagon floating badge
            if ($bandwagon['year'] == getCurrentYear() && $bandwagon['week'] == getCurrentWeek()) {
                $this->users[$bandwagon['chiefid']]['years'][$bandwagon['year']]['badges'][] = 19;
                $this->users[$bandwagon['chiefid']]['badges'][] = 19;
                $sql = "update userbadge set userid = {$bandwagon['chiefid']}, display = 'Chief of the Bandwagon: " . $this->_weeksOnBandwagon($this->users[$bandwagon['chiefid']], $bandwagon['year'], $bandwagon['week']) . " Consecutive Weeks Riding' where badgeid = 19";
                Yii::app()->db->createCommand($sql)->query();
            }
        }
        
        // for all users, figure out the times they successfully hopped off the bandwagon!
        // first, sort bandwagons into a structure so we don't have to constantly loop over them
        $bandwagonsByYear = array();
        foreach ($this->bandwagons as $bandwagon) {
            if (!array_key_exists($bandwagon['year'], $bandwagonsByYear)) {
                $bandwagonsByYear[$bandwagon['year']] = array();
            }
            $bandwagonsByYear[$bandwagon['year']][$bandwagon['week']] = $bandwagon;
        }
        // loop over users
        foreach ($this->users as &$user) {
            $lastBandwagonWeek      = 0;
            $lastBandwagonYear      = 0;
            $bandwagonCorrectStreak = 0;
            $wasOnBandwagonLastWeek = false;
            foreach ($user['years'] as $y=>$year) {
                if ($y == 2004) continue;
                foreach ($year['weeks'] as $w=>$week) {
                    if ($week['onBandwagon']) {
                        // the user was on the bandwagon this week
                        $wasOnBandwagonLastWeek = true;
                        if ($week['incorrect']) {
                            // the bandwagon was wrong -- reset the correct streak
                            $bandwagonCorrectStreak = 0;
                        } else {
                            // the bandwagon was right -- increment the streak
                            $bandwagonCorrectStreak++;
                        }
                    } else {
                        // the user was NOT on the bandwagon this week
                        if ($wasOnBandwagonLastWeek && $bandwagonCorrectStreak >= 3 && !$week['incorrect'] &&
                            array_key_exists($y, $bandwagonsByYear) && array_key_exists($w, $bandwagonsByYear[$y]) && $bandwagonsByYear[$y][$w]['incorrect']) {
                            // magical case:
                            // - the user was on the bandwagon last week
                            // - the user had been on a correct streak on the bandwagon for at least 3 weeks leading up to this week
                            // - the user got their pick right this week
                            // - the bandwagon was WRONG this week!
                            // echo "{$user['username']} hopped off the crashing bandwagon on week $w $y after riding for $bandwagonCorrectStreak weeks!<br />";                $sql = "replace into bandwagon (yr, week, chiefid, teamid, incorrect) values ({$bandwagon['year']}, {$bandwagon['week']}, $chiefId, $teamId, " . (is_null($incorrect) ? 'null' : $incorrect) . ")";
                            $sql = "replace into bandwagonjump (yr, week, userid, previous_weeks) values ($y, $w, {$user['id']}, $bandwagonCorrectStreak)";
                            Yii::app()->db->createCommand($sql)->query();
                            $user['years'][$y]['bandwagonJumper']++;
                            $user['bandwagonJumper']++;
                        }
                        $bandwagonCorrectStreak = 0;
                        $wasOnBandwagonLastWeek = false;
                    }
                }
            }
        }
    }
    
    private function _getPowerPoints($powerData, $userId=0) {
        $powerPoints = array();
        $multipliers = param('powerMultipliers');
        
        if ($powerData['totalPicks']) {
            $powerData['winPct'] = ($powerData['numCorrect'] / $powerData['totalPicks']) * 100;
            $powerData['avgMov'] = $powerData['totalMov']   / $powerData['totalPicks'];
        } else {
            $powerData['winPct'] = 0;
            $powerData['avgMov'] = 0;
        }
        
        // user gets points for every season they've played
        $powerPoints['seasons'] = $powerData['numSeasons'] * $multipliers['pointsPerSeason'];
        
        // user gets points for every correct pick
        $powerPoints['correct'] = $powerData['numCorrect'] * $multipliers['pointsPerWin'];
        
        // user gets their badge points, straight up
        $powerPoints['badges'] = $powerData['badgePoints'];
        
        // user gets points for all the money they've won
        $powerPoints['money'] = $powerData['money'] * $multipliers['pointsPerDollar'];
        
        // user gets points for win percentage, but the points ramp up in effectiveness for the first $r picks.
        // Once the user has $r picks or more, their winPct is fully effective.
        // Note:  The user will LOSE power points if they have a win percentage below the threshold
        $r = $multipliers['winPctRampUp'];
        $t = $multipliers['winPctThreshold'];
        $powerPoints['winPct'] = ($powerData['winPct'] - $t) * (min($r, max($powerData['totalPicks'], $powerData['totalPicks']-$r)) / $r) * $multipliers['winPctMultiplier'];
        
        // user gets points for margin of defeat
        $powerPoints['mov'] = ($powerData['avgMov'] * -1) * $multipliers['movPoints'];
        
        // user loses points for setbysystem
        $powerPoints['setBySystem'] = $powerData['numSetBySystem'] * $multipliers['pointsPerSetBySystem'];
        
        // user gets points for every post they make
        $powerPoints['talks'] = $powerData['numPostsBy'] * $multipliers['pointsPerTalk'];
        
        // user gets points for every player they referred
        // for me, points are divided by 10
        $powerPoints['referrals'] = ($powerData['numReferrals'] * $multipliers['pointsPerReferral']) / ($userId == 1 ? 10 : 1);

        // user gets points for every like they give
        $powerPoints['likesBy'] = $powerData['numLikesBy'] * $multipliers['pointsPerLikesBy'];

        // user gets points for every like given to them
        $powerPoints['likesAt'] = $powerData['numLikesAt'] * $multipliers['pointsPerLikesAt'];
        
        // user gets points for every first place finish
        $powerPoints['firstPlace'] = $powerData['numFirstPlace'] * $multipliers['pointsPerFirstPlace'];
        
        // user gets points for every second place finish
        $powerPoints['secondPlace'] = $powerData['numSecondPlace'] * $multipliers['pointsPerSecondPlace'];
                
        $powerPoints['points'] = 0;
        $powerPoints['points'] += $powerPoints['seasons'];
        $powerPoints['points'] += $powerPoints['correct'];
        $powerPoints['points'] += $powerPoints['badges'];
        $powerPoints['points'] += $powerPoints['money'];
        $powerPoints['points'] += $powerPoints['winPct'];
        $powerPoints['points'] += $powerPoints['mov'];
        $powerPoints['points'] += $powerPoints['setBySystem'];
        $powerPoints['points'] += $powerPoints['talks'];
        $powerPoints['points'] += $powerPoints['referrals'];
        $powerPoints['points'] += $powerPoints['likesBy'];
        $powerPoints['points'] += $powerPoints['likesAt'];
        $powerPoints['points'] += $powerPoints['firstPlace'];
        $powerPoints['points'] += $powerPoints['secondPlace'];
        $powerPoints['points'] = round($powerPoints['points'], 3);
        
        return $powerPoints;
    }

    private function _recalcPower() {
        
        // KDHTODO comment this much better
        
        // get the power point values for each badge
        $badgePoints = array();
        $sql = 'select id, power_points from badge';
        $rsBadges = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsBadges as $badge) {
            $badgePoints[$badge['id']] = $badge['power_points'];
        }
        
        
        foreach ($this->users as &$user) {
            $powerUser      = array();
            $weekIndex      = -1;    // the index of our flat power weeks array
            $lastYearBadges = array();
            //reset($user['years']);
            foreach ($user['years'] as $y=>&$year) {
                end($year['weeks']);
                $lastWeekForYear = key($year['weeks']);
                foreach ($year['weeks'] as $w=>&$week) {
                    // remember that weeks are CUMULATIVE from the beginning of time when determining power ranking
                    $previousWeek = (++$weekIndex > 0 ? $powerUser[$weekIndex-1] : null);
                    $powerWeek = array(
                        'year'           => $y,
                        'week'           => $w,
                        'numSeasons'     => ($previousWeek ? $previousWeek['numSeasons'] : 0),
                        'totalPicks'     => ($previousWeek ? $previousWeek['totalPicks'] : 0),
                        'numCorrect'     => ($previousWeek ? $previousWeek['numCorrect'] : 0),
                        'numSetBySystem' => ($previousWeek ? $previousWeek['numSetBySystem'] : 0),
                        'totalMov'       => ($previousWeek ? $previousWeek['totalMov'] : 0),
                        'badgePoints'    => ($previousWeek ? $previousWeek['badgePoints'] : 0),
                        'money'          => ($previousWeek ? $previousWeek['money'] : 0),
                        'numPostsBy'     => ($previousWeek ? $previousWeek['numPostsBy'] : 0),
                        'numLikesBy'     => ($previousWeek ? $previousWeek['numLikesBy'] : 0),
                        'numLikesAt'     => ($previousWeek ? $previousWeek['numLikesAt'] : 0),
                        'numReferrals'   => ($previousWeek ? $previousWeek['numReferrals'] : 0),
                        'numFirstPlace'  => ($previousWeek ? $previousWeek['numFirstPlace'] : 0),
                        'numSecondPlace' => ($previousWeek ? $previousWeek['numSecondPlace'] : 0),
                    );
                    // include the stuff that counts every week
                    $powerWeek['totalPicks']     += 1;
                    $powerWeek['numCorrect']     += ($week['incorrect'] ? 0 : 1);
                    $powerWeek['numSetBySystem'] += ($week['setbysystem'] ? 1 : 0);
                    $powerWeek['totalMov']       += $week['mov'];
                    if ($w == 1) {
                        // this is the first week of the year
                        // we can include number of seasons and referrals
                        $powerWeek['numSeasons']++;
                        $powerWeek['numReferrals'] += $year['referrals'];
                    }
                    if ($lastWeekForYear === $w) {
                        // this is the last week of the year
                        // we can include wins, money, talks, likes, and badges for this year now
                        $powerWeek['numFirstPlace']  += $year['firstPlace'];
                        $powerWeek['numSecondPlace'] += $year['secondPlace'];
                        $powerWeek['money']          += $year['money'];
                        $powerWeek['numPostsBy']     += $year['postsBy'];
                        $powerWeek['numLikesBy']     += $year['likesBy'];
                        $powerWeek['numLikesAt']     += $year['likesAt'];
                        // add all the badges awarded in all years up to this one, as long as they are not floatable
                        // this is so that we get a cumulative total of badges up until now, while knowing that floatable
                        // badges could be lost, and therefore should only count in the current year
                        $powerWeek['badgePoints'] = 0;
                        foreach ($user['years'] as $badgeY => $badgeYear) {
                            if ($badgeY > $y) continue;     // the badge year we're looking at is beyond the current year we're considering
                            foreach ($badgeYear['badges'] as $badgeId) {
                                // for each badge awarded in the badge year we're looking at
                                if ($badgeY == $y || array_search($badgeId, $this->floatingBadges) === false) {
                                    // if the badge year is the same as the year we're looking at, or if the badge is not floating (aka is permanent),
                                    // then we add it to our running cumulative total for the year we're looking at
                                    $powerWeek['badgePoints'] += $badgePoints[$badgeId];
                                }
                            }
                        }
                        $year['powerdata'] = $powerWeek;
                    }
                    // append the week to the array
                    $powerUser[] = $powerWeek;
                    $week['powerdata'] = $powerWeek;
                }
            }
        }
        
        
        // figure out every user's power points for every year
        foreach ($this->users as &$user) {
            $lastPowerPoints = 0;
            for ($y=param('earliestYear'); $y<=getCurrentYear(); $y++) {
                $thisPowerPoints = $lastPowerPoints;
                if (array_key_exists($y, $user['years'])) {
                    $thisPowerPointData = $this->_getPowerPoints($user['years'][$y]['powerdata'], $user['id']);
                    $thisPowerPoints = $thisPowerPointData['points'];
                    // store this user/year
                    $user['years'][$y]['powerpoints']    = $thisPowerPoints;
                    $user['years'][$y]['powerpointdata'] = $thisPowerPointData;
                }
                $lastPowerPoints = $thisPowerPoints;
            }
            $user['powerpoints'] = $lastPowerPoints;
        }
        
        
        // loop over every year and rank all users based on power points they have for that year
        for ($y=param('earliestYear'); $y<=getCurrentYear(); $y++) {
            $allValues = array();
            $places    = array();
            $ties      = array();
            // KDHTODO examine user's first year here
            foreach ($this->users as $user) {
                if (array_key_exists($y, $user['years'])) {
                    $allValues[] = $user['years'][$y]['powerpoints'];
                } else if ($user['firstYear'] < $y) {
                    // the user didn't play in the year we're looking at, but they played before that, so we should count their last known power points
                    for ($yf=$y; $yf>=param('earliestYear'); $yf--) {
                        if (array_key_exists($yf, $user['years'])) {
                            $allValues[] = $user['years'][$yf]['powerpoints'];
                            break;
                        }
                    }
                }
            }
            $this->_getPlacesAndTies($allValues, 'powerpoints', $places, $ties);
            foreach ($this->users as &$user) {
                if (array_key_exists($y, $user['years'])) {
                    $user['powerrank'] = array_search($user['years'][$y]['powerpoints'], $places);
                    $user['years'][$y]['powerrank'] = $user['powerrank'];
                } else if ($user['firstYear'] < $y) {
                    for ($yf=$y; $yf>=param('earliestYear'); $yf--) {
                        if (array_key_exists($yf, $user['years'])) {
                            $user['powerrank'] = array_search($user['years'][$yf]['powerpoints'], $places);
                            $user['years'][$yf]['powerrank'] = $user['powerrank'];
                            break;
                        }
                    }
                }
            }
        }
        
        
        // get the known power rankings from the last time this script ran, and only do inserts if something has changed
        $sql = 'select * from power order by userid, yr';
        $rsExistingPower = Yii::app()->db->createCommand($sql)->query();
        $existingPowers = array();
        foreach ($rsExistingPower as $ep) {
            if (!array_key_exists($ep['userid'], $existingPowers)) {
                $existingPowers[$ep['userid']] = array();
            }
            $existingPowers[$ep['userid']][$ep['yr']] = $ep;
        }
        foreach ($this->users as $user) {
            foreach ($user['years'] as $y=>$year) {
                if (array_key_exists('powerpoints', $year)) {
                    // for this user/year, try to find the matching existing power record
                    $needInsert = true;
                    $powerData = $user['years'][$y]['powerpointdata'];
                    $powerRank = $user['years'][$y]['powerrank'];
                    if (array_key_exists($user['id'], $existingPowers) &&
                        array_key_exists($y, $existingPowers[$user['id']])) {
                            $existingPower = $existingPowers[$user['id']][$y];
                            // do we need to insert new data
                            $needInsert = ($existingPower['powerpoints'] != $year['powerpoints'] || $existingPower['powerrank'] != $year['powerrank']);
                    }
                    if ($needInsert) {
                        $details = addslashes(json_encode($user['years'][$y]['powerpointdata']));
                        $sql = "replace into power (userid, yr,
                                    powerpoints, powerrank, seasonPts, correctPts, badgePts, moneyPts, winPctPts, movPts, setBySystemPts, talkPts, referralPts, likesByPts, likesAtPts, firstPlacePts, secondPlacePts, updated
                                ) values (
                                    {$user['id']}, $y,
                                    {$powerData['points']},
                                    {$powerRank},
                                    {$powerData['seasons']},
                                    {$powerData['correct']},
                                    {$powerData['badges']},
                                    {$powerData['money']},
                                    {$powerData['winPct']},
                                    {$powerData['mov']},
                                    {$powerData['setBySystem']},
                                    {$powerData['talks']},
                                    {$powerData['referrals']},
                                    {$powerData['likesBy']},
                                    {$powerData['likesAt']},
                                    {$powerData['firstPlace']},
                                    {$powerData['secondPlace']},
                                    NOW()
                                )";
                        // echo "$sql<br />";
                        Yii::app()->db->createCommand($sql)->query();
                    }
                }
            }
            $sql = "update user set power_points = {$powerData['points']}, power_ranking = {$powerRank} where id = {$user['id']}";
            Yii::app()->db->createCommand($sql)->query();
        }
        
    }
    
    public function actionIndex() {
        exit;
    }
    
    public function actionRecalc() {
        set_time_limit(300);
        $this->_recalcStats();
        echo 'done.';
    }
}