<?php
// KDHTODO clean up this file so these random functions aren't hanging around all over cluttering things up

/*
<ul>
    <li>Record per Year (show as bar graph?)</li>
    <li>Times on Bandwagon</li>
    <li>Times Off Bandwagon</li>
    <li>Times Dodging a Bandwagon Crash</li>
    <li>Times Being Bandwagon Chief</li>
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
        31 => 'postsAt',
        32 => 'postsBy',
        33 => 'likesAt',
        34 => 'likesBy',
        35 => 'referrals',
        36 => 'firstPlace',
        37 => 'secondPlace',
        38 => 'numTrophies',
        39 => 'numBadges',
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
                    'id'            => $u,
                    'username'      => $row['username'],
                    'active'        => (bool) $row['active'],
                    'entryFee'      => 0,
                    'money'         => 0,
                    'firstPlace'    => 0,
                    'secondPlace'   => 0,
                    'postsBy'       => 0,
                    'postsAt'       => 0,
                    'likesBy'       => 0,
                    'likesAt'       => 0,
                    'referrals'     => 0,
                    'currentStreak' => 0,
                    'numTrophies'   => 0,
                    'numBadges'     => 0,
                    'badges'        => array(),
                    'years'         => array(),
                    'pickTotals'    => $this->_pickTotalsArray(),
                );
                $currentStreak = 0;
                $lastUserId    = $u;
                $lastYear      = 0;
            }
            
            // on a new year?
            if ($y != $lastYear) {
                $user['years'][$y] = array(
                    'weeks'          => array(),
                    'pendingPicks'   => array(),    // key = week#, value = teamid
                    'entryFee'       => $this->_getEntryFee($y),
                    'money'          => 0,
                    'firstPlace'     => 0,
                    'secondPlace'    => 0,
                    'postsBy'        => 0,
                    'postsAt'        => 0,
                    'likesBy'        => 0,
                    'likesAt'        => 0,
                    'firstIncorrect' => 22,
                    'badges'         => array(),
                    'pickTotals'     => $this->_pickTotalsArray(),
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
        $sql = 'select * from losertalk where active = 1' . ($userId ? " and (postedby = $userId or postedat = $userId)" : '');
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
        // KDHTODO should I have special rules for myself since I have so many more referrals?
        $sql = 'select u.referrer, count(u.id) num from user u where u.referrer is not null group by u.referrer';
        $rsReferral = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsReferral as $row) {
            if (array_key_exists($row['referrer'], $users)) {
                $users[$row['referrer']]['referrals'] = (int) $row['num'];
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
        // KDHTODO (later) this should populate $user['powerrank']
        //<li>Current Power Ranking</li>
        //<li>Highest Power Ranking</li>
        //<li>Lowest Power Ranking</li>
        //<li>Largest One-Week Power Ranking Jump</li>
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
        
        // KDHTODO for these floating badges, can we do them by WEEK too, so we can record all floating badges a user had at any point in time?
        
        // 12 - monkey on my back
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
        
        // 13 - on fire
        // 14 - all-time on fire
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
        
        // 16 - total defeat
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
        
        // 18 - .800 club
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
        $sql = 'select ub.userid, b.id, ub.yr, b.unlocked_year from badge b inner join userbadge ub on ub.badgeid = b.id where b.unlocked_year is not null';
        $rsBadge = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsBadge as $row) {
            if (array_key_exists($row['userid'], $this->users)) {
                $this->users[$row['userid']]['badges'][] = $row['id'];
                $this->users[$row['userid']]['badges'] = array_unique($this->users[$row['userid']]['badges']);
                if ($row['yr']) {
                    $this->users[$row['userid']]['years'][$row['yr']]['badges'][] = $row['id'];
                    $this->users[$row['userid']]['years'][$row['yr']]['badges'] = array_unique($this->users[$row['userid']]['years'][$row['yr']]['badges']);
                }
            }
        }
        
    }
    
    private function _recalcPower() {
        // KDHTODO should update $user['powerrank']
        // KDHTODO allow calc of every week of every year so entire power history is available
            // this would require knowing which/how many badges they had AT THE TIME
            // No it wouldn't -- we're going to make a rule that points from badges/talks/likes only come at the end of the year
        foreach ($this->users as &$user) {
            // KDHTODO figure this out for real
            $user['powerrank'] = $user['id'];
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
            $this->bandwagons[] = array(
                'year'    => $bandwagon['year'],
                'week'    => $bandwagon['week'],
                'chiefid' => $chiefId,
                'teamid'  => $teamId
            );
            
            // insert the bandwagon into the database if necessary
            $insert = true;
            foreach ($existingBandwagons as $eb) {
                if ($eb['yr'] == $bandwagon['year'] && $eb['week'] == $bandwagon['week'] && $eb['teamid'] == $teamId && $eb['chiefid'] == $chiefId) {
                    $insert = false;
                    break;
                }
                if ($eb['yr'] > $bandwagon['year'] || ($eb['yr'] == $bandwagon['year'] && $eb['week'] > $bandwagon['week'])) {
                    break;
                }
            }
            if ($insert) { 
                $sql = "replace into bandwagon (yr, week, chiefid, teamid) values ({$bandwagon['year']}, {$bandwagon['week']}, $chiefId, $teamId)";
                // echo "$sql<br />";
                Yii::app()->db->createCommand($sql)->query();
            }
        }
        
        // KDHTODO should assign bandwagon chief badge (id 19)
        // KDHTODO add "incorrect" column to the bandwagon table so we can identify cases where the user dodged a crash or hopped on at the right time
            // might need to do this by modifying the $users array to indicate on each pick whether or not it was a bandwagon pick
        // KDHTODO (later)
        //<li>Times on Bandwagon</li>
        //<li>Times Off Bandwagon</li>
        //<li>Times Dodging a Bandwagon Crash</li>
        //<li>Times Being Bandwagon Chief</li>
    }
    
    public function actionIndex() {
        exit;
    }
    
    public function actionRecalc() {
        $this->_recalcStats();
        echo 'done.';
    }
}