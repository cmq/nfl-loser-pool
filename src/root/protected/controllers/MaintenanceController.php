<?php
class MaintenanceController extends Controller
{
    
    private $reverseStats = null;
    private $users        = array();
    private $bandwagons   = array();
    private $floatingBadges = array();
    
    public function filters() {
        /*
        return array(
            array('application.filters.SuperadminFilter'),
        );
        */
    }
    
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
    
    private function _getPreviousWeek($user, $endYear, $endWeek, $hardcore=0) {
        $previous = null;
        $yearKey = $hardcore ? 'yearsHardcore' : 'years';
        foreach ($user[$yearKey] as $y=>$year) {
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
            if (isset($user['powerrank']) && $user['powerrank'] < $bestPowerRank) {
                $bestPowerRank = $user['powerrank'];
                $highestUser   = $user;
            }
        }
        return $highestUser;
    }
    
    private function _userHasPick($user, $year, $week, $teams, $searchPending=false, $hardcore=0) {
        $yearKey = $hardcore ? 'yearsHardcore' : 'years';
        if (!is_array($teams)) {
            $teams = array($teams);
        }
        if (array_key_exists($year, $user[$yearKey]) &&
            isset($user[$yearKey][$year]['weeks']) &&    // this can happen when it's week 0 and users exist for a year but haven't made any picks for it yet
            is_array($user[$yearKey][$year]['weeks']) && // this can happen when it's week 0 and users exist for a year but haven't made any picks for it yet
            array_key_exists($week, $user[$yearKey][$year]['weeks']) &&
            array_search($user[$yearKey][$year]['weeks'][$week]['teamid'], $teams) !== false) {
            // this user has this year/week and one of the teams in the list
            return true;
        }
        if ($searchPending) {
            if (array_key_exists($year, $user[$yearKey]) &&
                isset($user[$yearKey][$year]['pendingPicks']) &&     // this can happen when it's week 0 and users exist for a year but haven't made any picks for it yet
                is_array($user[$yearKey][$year]['pendingPicks']) &&  // this can happen when it's week 0 and users exist for a year but haven't made any picks for it yet
                array_key_exists($week, $user[$yearKey][$year]['pendingPicks']) &&
                array_search($user[$yearKey][$year]['pendingPicks'][$week], $teams) !== false) {
                // this user has this year/week and one of the teams in the list
                return true;
            }
        }
        return false;
    }
    
    private function _weeksOnBandwagon($user, $toYear, $toWeek, $hardcore=0) {
        $b = array_reverse($this->bandwagons);
        $yearKey = $hardcore ? 'yearsHardcore' : 'years';
        $weeksOn = 0;
        foreach ($b as $bandwagon) {
            if ($bandwagon['year'] > $toYear || ($bandwagon['year'] == $toYear && $bandwagon['week'] > $toWeek)) {
                // we haven't traveled backwards far enough yet
                continue;
            }
            if (isset($user[$yearKey][$bandwagon['year']]['weeks'][$bandwagon['week']]) || isset($user[$yearKey][$bandwagon['year']]['pendingPicks'][$bandwagon['week']])) {
                if ($this->_userHasPick($user, $bandwagon['year'], $bandwagon['week'], $bandwagon['teamid'], true, $hardcore)) {
                    // the user has the bandwagon pick for this week
                    if ($weeksOn < 0) {
                        // the user was previously NOT on the bandwagon, and that streak is now over
                        break;
                    }
                    // otherwise, just increase their weeks on the bandwagon
                    $weeksOn++;
                } else {
                    // the user does NOT have the bandwagon pick for this week
                    if ($weeksOn > 0) {
                        // the user was previously ON the bandwagon, and that streak is now over
                        break;
                    }
                    // otherwise, just decrease their weeks on the bandwagon
                    $weeksOn--;
                }
            }
        }
        return $weeksOn;
    }
    
    private function _buildStreaks(&$user, $hardcore=0) {
        $yearKey = $hardcore ? 'yearsHardcore' : 'years';
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
        foreach ($user[$yearKey] as $y=>$year) {
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
                $previousWeek = $this->_getPreviousWeek($user, $correct['startYear'], $correct['startWeek'], $hardcore);
                if ($previousWeek && $user[$yearKey][$previousWeek['y']]['weeks'][$previousWeek['w']]['streak'] > 0) {
                    $correct['startYear'] = $previousWeek['y'];
                    $correct['startWeek'] = $previousWeek['w'];
                    if ($user[$yearKey][$previousWeek['y']]['weeks'][$previousWeek['w']]['streak'] === 1) {
                        $done = true;
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
                $previousWeek = $this->_getPreviousWeek($user, $incorrect['startYear'], $incorrect['startWeek'], $hardcore);
                if ($previousWeek && $user[$yearKey][$previousWeek['y']]['weeks'][$previousWeek['w']]['streak'] < 0) {
                    $incorrect['startYear'] = $previousWeek['y'];
                    $incorrect['startWeek'] = $previousWeek['w'];
                    if ($user[$yearKey][$previousWeek['y']]['weeks'][$previousWeek['w']]['streak'] === -1) {
                        $done = true;
                    }
                } else {
                    $done = true;
                }
            }
        }
        
        $incorrect['length'] = abs($incorrect['length']);
        
        // save the streaks in the user
        $user['longCorrectStreak']   = max(isset($user['longCorrectStreak']) ? $user['longCorrectStreak'] : 0, $correct);
        $user['longIncorrectStreak'] = max(isset($user['longIncorrectStreak']) ? $user['longIncorrectStreak'] : 0, $incorrect);
    }
    
    private function _getStatFromUser($user, $key, $y=null, $hardcore=0) {
        $value      = null;
        $collection = $user;
        $fetchKey   = $key;
        $yearKey    = $hardcore ? 'yearsHardcore' : 'years';
        
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
                if ($y && array_key_exists($y, $user[$yearKey])) {
                    $collection = $user[$yearKey][$y]['pickTotals'];
                } else {
                    $y = null;
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
            if (array_key_exists($y, $collection[$yearKey]) && array_key_exists($fetchKey, $collection[$yearKey][$y])) {
                $value = $collection[$yearKey][$y][$fetchKey];
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
                $totalPicks = $this->_getStatFromUser($user, 'pickstotal', $y, $hardcore);
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
    
    private function _calculatePlaces($key, $y=null, $hardcore=0) {
        $places     = array();  // keyed on the place, with the value equal to the value in that place
        $placesa    = array();  // keyed on the place, with the value equal to the value in that place -- active users only
        $ties       = array();  // simple array of values representing places in which there's a tie
        $tiesa      = array();  // simple array of values representing places in which there's a tie -- active users only
        $allValues  = array();
        $allValuesa = array();
    
        foreach ($this->users as $user) {
            $val = $this->_getStatFromUser($user, $key, $y, $hardcore);
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
    
    private function _insertStat($statId, $hardcore=0) {
        $statKey   = $this->STATS[$statId];
        $placeData = $this->_calculatePlaces($statKey, $hardcore);
        foreach ($this->users as $u=>$user) {
            $userValue   = $this->_getStatFromUser($user, $statKey, $hardcore);
            $place       = array_search($userValue, $placeData['places']);
            $activePlace = array_search($userValue, $placeData['activePlaces']);
            $tied        = array_search($place, $placeData['ties']) !== false ? 1 : 0;
            $activeTied  = array_search($place, $placeData['activeTies']) !== false ? 1 : 0;
            $meta1       = '';
            $meta2       = '';
            if ($statKey == 'streakcorrect' || $statKey == 'streakincorrect') {
                $streakKey = ($statKey == 'streakcorrect' ? 'longCorrectStreak' : 'longIncorrectStreak');
                $meta1 = getWeekName($user[$streakKey]['startWeek'], $user[$streakKey]['startYear'], true) . ', ' . $user[$streakKey]['startYear'];
                $meta2 = getWeekName($user[$streakKey]['endWeek'], $user[$streakKey]['endYear'], true) . ', ' . $user[$streakKey]['endYear'];
            }
            $activePlace = $activePlace === false ? 0 : $activePlace;
            if (!$userValue) {
                $userValue = 0;
                $place = 999;
            }
            $sql = "insert into userstat (userid, statid, hardcore, place, placeactive, tied, tiedactive, value, meta1, meta2) values ($u, $statId, $hardcore, $place, $activePlace, $tied, $activeTied, $userValue, '$meta1', '$meta2')";
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
        		order by    u.id, p.hardcore, p.yr, p.week';
        $lastUserId = 0;
        $rsAll = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsAll as $row) {    // there will only be one
            $u = $row['id'];
            $y = $row['yr'];
            $w = $row['week'];
            $h = $row['hardcore'];
            $yearKey = $h ? 'yearsHardcore' : 'years';
            
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
                    'yearsHardcore'   => array(),
                    'pickTotals'      => $this->_pickTotalsArray(),
                );
                $currentStreak = 0;
                $lastUserId    = $u;
                $lastYear      = 0;
                $lastHardcore  = 0;
            }
            
            // on a new year or new hardcore?
            if ($h != $lastHardcore || $y != $lastYear) {
                $user['firstYear'] = min($user['firstYear'], $y);
                $user['lastYear']  = max($user['lastYear'],  $y);
                $user[$yearKey][$y] = array(
                    'weeks'           => array(),
                    'pendingPicks'    => array(),    // key = week#, value = teamid
                    'entryFee'        => $this->_getEntryFee($y),
                    'money'           => 0,
                    'firstPlace'      => 0,
                    'secondPlace'     => 0,
                    'postsBy'         => 0,     // cumulative told is only stored in 'years' array, not 'yearsHardcore' array
                    'postsAt'         => 0,     // cumulative told is only stored in 'years' array, not 'yearsHardcore' array
                    'likesBy'         => 0,     // cumulative told is only stored in 'years' array, not 'yearsHardcore' array
                    'likesAt'         => 0,     // cumulative told is only stored in 'years' array, not 'yearsHardcore' array
                    'referrals'       => 0,     // cumulative told is only stored in 'years' array, not 'yearsHardcore' array
                    'numBandwagons'   => 0,
                    'bandwagonChief'  => 0,
                    'bandwagonJumper' => 0,
                    'firstIncorrect'  => 22,
                    'badges'          => array(),     // cumulative told is only stored in 'years' array, not 'yearsHardcore' array
                    'pickTotals'      => $this->_pickTotalsArray(),
                );
                $user['entryFee'] += $user[$yearKey][$y]['entryFee'];
                $lastYear = $y;
                $lastHardcore = $h;
            }
            
            // record the pick itself
            if (is_null($row['incorrect'])) {
                // this is a pending pick.  We don't want it recorded as part of the normal set of picks,
                // but we still want to hold onto it because the bandwagon calculations use it
                $user[$yearKey][$y]['pendingPicks'][$w] = $row['teamid'];
            } else {
                // this is a normal pick that we want to record for all normal calculations
                $user[$yearKey][$y]['weeks'][$w] = array(
                    'teamid'      => $row['teamid'],
                    'mov'         => (int)  $row['mov'],
                    'incorrect'   => (bool) $row['incorrect'],
                    'setbysystem' => (bool) $row['setbysystem'],
                    'streak'      => 0,
                    'onBandwagon' => false
                );
                
                // update totals and other counters
                $user[$yearKey][$y]['firstIncorrect'] = ($row['incorrect'] ? min($row['week'], $user[$yearKey][$y]['firstIncorrect']) : $user[$yearKey][$y]['firstIncorrect']);
                $this->_updatePickTotals($row, $user['pickTotals']);
                $this->_updatePickTotals($row, $user[$yearKey][$y]['pickTotals']);
                
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
                    $user[$yearKey][$y]['weeks'][$w]['streak'] = $currentStreak;
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
            try {
                $yearKey = $row['hardcore'] ? 'yearsHardcore' : 'years';
                $users[$row['userid']]['numTrophies'] += 1;
                $users[$row['userid']]['money']       += $row['winnings'];
                $users[$row['userid']]['firstPlace']  += $row['place'] == 1 ? 1 : 0;
                $users[$row['userid']]['secondPlace'] += $row['place'] == 2 ? 1 : 0;
                $users[$row['userid']][$yearKey][$row['yr']]['money']       += $row['winnings'];
                $users[$row['userid']][$yearKey][$row['yr']]['firstPlace']  += $row['place'] == 1 ? 1 : 0;
                $users[$row['userid']][$yearKey][$row['yr']]['secondPlace'] += $row['place'] == 2 ? 1 : 0;
            } catch (Exception $e) {
                echo '<pre>';var_dump($users[$row['userid']]);exit;
            }
        }
        
        
        // get talk/like info
        $sql = 'select * from losertalk where active = 1 and admin = 0' . ($userId ? " and (postedby = $userId or postedat = $userId)" : '');
        $rsTalk = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsTalk as $row) {
            if (array_key_exists($row['postedby'], $users) && array_key_exists($row['yr'], $users[$row['postedby']]['years'])) {
                $users[$row['postedby']]['postsBy']++;
                $users[$row['postedby']]['years'][$row['yr']]['postsBy']++;
            }
            if (array_key_exists($row['postedat'], $users) && array_key_exists($row['yr'], $users[$row['postedat']]['years'])) {
                $users[$row['postedat']]['postsAt']++;
                $users[$row['postedat']]['years'][$row['yr']]['postsAt']++;
            }
        }
        $sql = 'select t.postedby, l.userid, l.yr from losertalk t inner join likes l on l.talkid = t.id where l.active = 1' . ($userId ? " and (t.postedby = $userId or l.userid = $userId)" : '');
        $rsLike = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsLike as $row) {
            if (array_key_exists($row['postedby'], $users) && array_key_exists($row['yr'], $users[$row['postedby']]['years'])) {
                $users[$row['postedby']]['likesBy']++;
                $users[$row['postedby']]['years'][$row['yr']]['likesBy']++;
            }
            if (array_key_exists($row['userid'], $users) && array_key_exists($row['yr'], $users[$row['userid']]['years'])) {
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
            $user['numSeasons'] = count($user['years']) + count($user['yearsHardcore']);
            $user['roi']        = $user['money'] / $user['entryFee'];
            
            $has2004            = false;
            $countableSeasons   = count($user['years']) + count($user['yearsHardcore']);
            $countableCorrect   = $user['pickTotals']['correct'];
            $countableIncorrect = $user['pickTotals']['incorrect'];
            $totalWeekIncorrect = 0;
            foreach ($user['years'] as $y=>$year) {
                $totalWeekIncorrect += $year['firstIncorrect'];
            }
            foreach ($user['yearsHardcore'] as $y=>$year) {
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
            
            $this->_buildStreaks($user, 0);
            $this->_buildStreaks($user, 1);
        }
        unset($user);


        // set the internal users array for the following functions to use
        $this->users = $users;
        
        // number of badges for each user (also by year)
        $this->_recalcBadges();     // recalculate the floating and losable badges
        
        // recalculate the power ranking
        $this->_recalcPower();
        
        // recalculate bandwagons and bandwagon badges/stats
        $this->_recalcBandwagon(0);
        $this->_recalcBandwagon(1);
        
        // empty out any previous userstat values
        $sql = 'delete from userstat';
        $rs = Yii::app()->db->createCommand($sql)->query();
        
        // insert all the new values
        foreach ($this->STATS as $s=>$stat) {
            $this->_insertStat($s, 0);
            $this->_insertStat($s, 1);
        }
    }
    
    private function _calculateBadge_monkey($y) {
        $sql = '
        	select		user.id, count(loserpick.week) numright
        	from		user
        	inner join	loserpick on loserpick.userid = user.id
        				and loserpick.incorrect = 0
                        and loserpick.setbysystem = 0
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
        	select		user.id, loserpick.yr, loserpick.week, loserpick.incorrect, loserpick.hardcore
        	from		user
        	inner join	loserpick on loserpick.userid = user.id
        				and loserpick.incorrect is not null
        				and loserpick.yr > 2004
                        and loserpick.yr <= ' . $y . '
            inner join  loseruser on loseruser.userid = user.id
                        and loseruser.yr = ' . $y . '
            where		1 = 1
        	order by	user.id, loserpick.yr, loserpick.week, loserpick.hardcore';
        $rsStreak = Yii::app()->db->createCommand($sql)->query();
        $streaks = array();
        $highCurrent = 0;
        $highAlltime = 0;
        $lastUserId = 0;
        $lastHardcore = null;
        foreach ($rsStreak as $row) {
            if ($row['id'] != $lastUserId || $row['hardcore'] != $lastHardcore) {
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
                $lastHardcore = $row['hardcore'];
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
            $sql = 'update userbadge set userid=0 where badgeid in (13,14)';    // reset the onfire badges before we re-calc them
            Yii::app()->db->createCommand($sql)->query();
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
    
    private function _recalcBandwagon($hardcore=0) {
        $sql = 'select * from bandwagon where hardcore = ' . $hardcore . ' order by yr, week';
        $rsExistingBandwagons = Yii::app()->db->createCommand($sql)->query();
        $existingBandwagons = array();
        foreach ($rsExistingBandwagons as $eb) {
            $existingBandwagons[] = $eb;
        }
        $yearKey = $hardcore ? 'yearsHardcore' : 'years';
        
        // collect new bandwagons for every year/week that hasn't already been calculated (including multiple teams if there are ties)
        $sql = '
            select      loserpick.teamid, loserpick.yr, loserpick.week, count(*) total
            from        loserpick
            where       loserpick.teamid > 0
                        and loserpick.hardcore = ' . $hardcore . '
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
                    if ($this->_userHasPick($user, $newBandwagon['year'], $newBandwagon['week'], $newBandwagon['teams'], true, $hardcore)) {
                        // this user has this year/week and one of the teams in the bandwagon
                        $users[] = $user;
                    }
                }
                // find which of these users has the highest power ranking and take their team as the only candidate
                $highestRankedUser = $this->_getHighestRankedUser($users);
                if ($highestRankedUser) {
                    if (isset($highestRankedUser[$yearKey][$newBandwagon['year']]['weeks'][$newBandwagon['week']])) {
                        $newBandwagon['teams'] = array($highestRankedUser[$yearKey][$newBandwagon['year']]['weeks'][$newBandwagon['week']]['teamid']);
                    } else if (isset($highestRankedUser[$yearKey][$newBandwagon['year']]['pendingPicks'][$newBandwagon['week']])) {
                        $newBandwagon['teams'] = array($highestRankedUser[$yearKey][$newBandwagon['year']]['pendingPicks'][$newBandwagon['week']]);
                    }
                }
            }
        }
        unset($newBandwagon);
        
        // figure out the chief of each bandwagon and insert it

        foreach ($newBandwagons as $bandwagon) {
            $teamId          = $bandwagon['teams'][0];
            $chiefId         = 0;
            $chiefCandidates = array();
            
            // get all the candidates (users who selected the bandwagon team for this week)
            foreach ($this->users as $user) {
                if ($this->_userHasPick($user, $bandwagon['year'], $bandwagon['week'], $teamId, true, $hardcore)) {
                    $chiefCandidates[] = $user;
                }
            }
            
            // determine how long each candidate has been on the bandwagon
            $maxTimeOnBandwagon     = -9999;
            $revisedChiefCandidates = array();
            foreach ($chiefCandidates as $chief) {
                $timeOnBandwagon = $this->_weeksOnBandwagon($chief, $bandwagon['year'], $bandwagon['week'], $hardcore);
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
            if (count($chiefCandidates) < 1) continue;  // this was 2004, when certain weeks had no picks because the season ended early
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
            if ($this->_userHasPick($this->users[$chiefId], $bandwagon['year'], $bandwagon['week'], $teamId, false, $hardcore)) {
                $incorrect = $this->users[$chiefId][$yearKey][$bandwagon['year']]['weeks'][$bandwagon['week']]['incorrect'];
                if (!is_null($incorrect)) {
                    $incorrect = (int) $incorrect;
                }
            }
            $this->bandwagons[] = array(
                'year'      => $bandwagon['year'],
                'week'      => $bandwagon['week'],
                'chiefid'   => $chiefId,
                'teamid'    => $teamId,
                'incorrect' => $incorrect,
                'hardcore'  => $hardcore
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
                $sql = "replace into bandwagon (yr, week, chiefid, teamid, hardcore, incorrect) values ({$bandwagon['year']}, {$bandwagon['week']}, $chiefId, $teamId, $hardcore, " . (is_null($incorrect) ? 'null' : $incorrect) . ")";
                Yii::app()->db->createCommand($sql)->query();
            }
        }
        
        // figure out each user that is on each bandwagon
        foreach ($this->bandwagons as $bandwagon) {
            $teamId = $bandwagon['teamid'];
            if ($bandwagon['hardcore'] != $hardcore) continue;
            foreach ($this->users as &$user) {
                if ($this->_userHasPick($user, $bandwagon['year'], $bandwagon['week'], $teamId, false, $hardcore)) {
                    $user[$yearKey][$bandwagon['year']]['weeks'][$bandwagon['week']]['onBandwagon'] = true;
                    $user[$yearKey][$bandwagon['year']]['numBandwagons']++;
                    $user['numBandwagons']++;
                }
                if ($bandwagon['chiefid'] == $user['id']) {
                    $user[$yearKey][$bandwagon['year']]['bandwagonChief']++;
                    $user['bandwagonChief']++;
                }
            }
            unset($user);
            // set chief of the bandwagon floating badge
            if ($bandwagon['year'] == getCurrentYear() && $bandwagon['week'] == getCurrentWeek()) {
                $this->users[$bandwagon['chiefid']][$yearKey][$bandwagon['year']]['badges'][] = 19;
                $this->users[$bandwagon['chiefid']]['badges'][] = 19;
                $sql = "update userbadge set userid = {$bandwagon['chiefid']}, display = 'Chief of the Bandwagon: " . $this->_weeksOnBandwagon($this->users[$bandwagon['chiefid']], $bandwagon['year'], $bandwagon['week'], $hardcore) . " Consecutive Weeks Riding' where badgeid = 19";
                Yii::app()->db->createCommand($sql)->query();
            }
        }
        
        // for all users, figure out the times they successfully hopped off the bandwagon!
        // first, sort bandwagons into a structure so we don't have to constantly loop over them
        $bandwagonsByYear = array();
        foreach ($this->bandwagons as $bandwagon) {
            if ($bandwagon['hardcore'] != $hardcore) continue;
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
            foreach ($user[$yearKey] as $y=>$year) {
                if (!isset($year['weeks']) || !is_array($year['weeks'])) {
                    // this happens when it's week 0 and users exist for a year but haven't made any picks for it yet
                    continue;
                }
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
                            $sql = "replace into bandwagonjump (yr, week, userid, hardcore, previous_weeks) values ($y, $w, {$user['id']}, $hardcore, $bandwagonCorrectStreak)";
                            Yii::app()->db->createCommand($sql)->query();
                            $user[$yearKey][$y]['bandwagonJumper']++;
                            $user['bandwagonJumper']++;
                        }
                        $bandwagonCorrectStreak = 0;
                        $wasOnBandwagonLastWeek = false;
                    }
                }
            }
        }
        unset($user);
        
        // left this code here just once and updated the userId's in the continue statement so I could process the full history just once
        /*
        foreach ($this->users as $user) {
            if ($user['id'] < 121 || $user['id'] > 150) continue;
            foreach ($user[$yearKey] as $y=>$year) {
                foreach ($year['weeks'] as $w=>$week) {
                    if (isset($user[$yearKey][$y]['weeks'][$w]) || isset($user[$yearKey][$y]['pendingPicks'][$w])) {
                        $weeksOnBandwagon = $this->_weeksOnBandwagon($user, $y, $w, $hardcore);
                        $sql = "update loserpick set weeks_on_bandwagon = $weeksOnBandwagon where userid = {$user['id']} and week = $w and yr = $y";
                        Yii::app()->db->createCommand($sql)->query();
                    }
                }
            }
        }
        */
        
        // for all users that have a pick for the current week,
        // figure out how long they've been on or off the bandwagon up to this week
        
        $curYear = getCurrentYear();
        $curWeek = getCurrentWeek();
        foreach ($this->users as $user) {
            if (isset($user[$yearKey][$curYear]['weeks'][$curWeek]) || isset($user[$yearKey][$curYear]['pendingPicks'][$curWeek])) {
                $weeksOnBandwagon = $this->_weeksOnBandwagon($user, $curYear, $curWeek, $hardcore);
                $sql = "update loserpick set weeks_on_bandwagon = $weeksOnBandwagon where userid = {$user['id']} and week = $curWeek and yr = $curYear and hardcore = $hardcore";
                Yii::app()->db->createCommand($sql)->query();
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
            $lastPowerData  = null;
            //reset($user['years']);
            foreach ($user['years'] as $y=>&$year) {
                // start by defaulting this year's power data to the last known power data
                if ($lastPowerData) {
                    $year['powerdata'] = $lastPowerData;
                    if (!isset($year['weeks']) || !is_array($year['weeks'])) {
                        // this happens when it's week 0 and users exist for a year but haven't made any picks for it yet
                        $year['powerdata']['numSeasons']++;
                        continue;
                    }
                }
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
                        $lastPowerData     = $powerWeek;
                    }
                    // append the week to the array
                    $powerUser[] = $powerWeek;
                    $week['powerdata'] = $powerWeek;
                }
                unset($week);
            }
            unset($year);
        }
        unset($user);
        
        
        // figure out every user's power points for every year
        foreach ($this->users as &$user) {
            $lastPowerPoints = 0;
            for ($y=param('earliestYear'); $y<=getCurrentYear(); $y++) {
                $thisPowerPoints = $lastPowerPoints;
                if (array_key_exists($y, $user['years']) && isset($user['years'][$y]['powerdata'])) {
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
        unset($user);
        
        
        // loop over every year and rank all users based on power points they have for that year
        for ($y=param('earliestYear'); $y<=getCurrentYear(); $y++) {
            $allValues = array();
            $places    = array();
            $ties      = array();
            foreach ($this->users as $user) {
                if (array_key_exists($y, $user['years']) && isset($user['years'][$y]['powerpoints'])) {
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
                $user['missingYears'] = array();
                if (array_key_exists($y, $user['years']) && isset($user['years'][$y]['powerpoints'])) {
                    $user['powerrank'] = array_search($user['years'][$y]['powerpoints'], $places);
                    $user['years'][$y]['powerrank'] = $user['powerrank'];
                } else if ($user['firstYear'] < $y) {
                    for ($yf=$y; $yf>=param('earliestYear'); $yf--) {
                        if (array_key_exists($yf, $user['years'])) {
                            $user['powerrank'] = array_search($user['years'][$yf]['powerpoints'], $places);
                            // store the record of the user's points/rank for this year, even though the user didn't PLAY in this year
                            $user['missingYears'][$y] = array(
                                'powerpointdata' => $user['years'][$yf]['powerpointdata'],
                                'powerpoints'    => $user['years'][$yf]['powerpoints'],
                                'powerrank'      => $user['powerrank']
                            );
                            break;
                        }
                    }
                }
            }
            unset($user);
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
            $userFinalRank = null;
            $userFinalPts  = 0;
            for ($y=param('earliestYear'); $y<=getCurrentYear(); $y++) {
                if (array_key_exists($y, $user['years'])) {
                    $year = $user['years'][$y];
                } else if (array_key_exists($y, $user['missingYears'])) {
                    $year = $user['missingYears'][$y];
                } else {
                    continue;
                }
                if (array_key_exists('powerpoints', $year)) {
                    // for this user/year, try to find the matching existing power record
                    $needInsert = true;
                    $powerData = $year['powerpointdata'];
                    $powerRank = $year['powerrank'];
                    if (array_key_exists($user['id'], $existingPowers) &&
                        array_key_exists($y, $existingPowers[$user['id']])) {
                            $existingPower = $existingPowers[$user['id']][$y];
                            // do we need to insert new data
                            $needInsert = ($existingPower['powerpoints'] != $year['powerpoints'] || $existingPower['powerrank'] != $year['powerrank']);
                    }
                    $userFinalRank = $powerRank;
                    $userFinalPts  = $powerData['points'];
                    if ($needInsert) {
                        $details = addslashes(json_encode($powerData));
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
            if ($userFinalRank) {
                $sql = "update user set power_points = {$userFinalPts}, power_ranking = {$userFinalRank} where id = {$user['id']}";
                Yii::app()->db->createCommand($sql)->query();
            }
        }
        
    }
    
    public function actionIndex() {
        exit;
    }
    
    public function actionRecalc() {
        set_time_limit(300);
        $this->_recalcStats();
        echo 'done.<br /><a href="/">Home</a>';
    }
    
    public function actionReminder ()
    {
        $y    = getCurrentYear();
        $w    = getCurrentWeek() + 1;   // add 1 because we care about NEXT week (the week we're reminding for)
        $wn   = getWeekName($w, $y, true);
        $bcc  = array();
        $bcch = array();
        $send = isset($_GET['send']);
        
        if ($w < 22) {
            $sql = "
                select      distinct u.id, u.email, u.receive_reminders, u.reminder_buffer, u.reminder_always, p.teamid, lu.hardcore
                from        user u
                inner join  loseruser lu on lu.userid = u.id and lu.yr = $y
                left join   loserpick p on p.userid = u.id and p.yr = $y and p.week = $w and p.hardcore = lu.hardcore
                where       u.active = 1
                            and not exists (
                                select * from reminders r where
                                    r.userid = u.id
                                    and r.yr = $y
                                    and r.week = $w
                                    and r.hardcore = lu.hardcore
                            )";
            $users = Yii::app()->db->createCommand($sql)->query();
            foreach ($users as $user) {
                if (!$user['receive_reminders']) continue;  // the user doesn't want reminders
                if (!$user['reminder_always'] && (int) $user['teamid'] > 0) continue;   // the user doesn't want reminders if they've already made a pick
                // check their reminder buffer
                $now = new DateTime();
                $now->add(new DateInterval("PT{$user['reminder_buffer']}H"));
                $locktime = getLockTime($w);
                $hc = ($user['hardcore'] ? 1 : 0);
                if ($now < $locktime) continue; // we're not within the user's reminder buffer yet
                // if we get here, it's time to send this user an email
                if ($hc) {
                    $bcch[] = $user['email'];
                } else {
                    $bcc[] = $user['email'];
                }
                if ($send) {
                    $sql = "insert into reminders (userid, email, yr, week, hardcore, created) values ({$user['id']}, '" . addslashes($user['email']) . "', $y, $w, $hc, NOW())";
                    Yii::app()->db->createCommand($sql)->query();
                }
            }
            
            ob_start();
            ?>
            This is an automated reminder to make or double-check your pick for <?php echo $wn;?> of the <?php echo $y;?> NFL Loser Pool.
            
            https://loserpool.kdhstuff.com
            <?php
            $body   = ob_get_clean();
            $bcc    = implode(',', array_unique($bcc));
            $bcch   = implode(',', array_unique($bcch));
            $from   = param('systemEmail');
            
            // send reminder to regulars
            $subject = "$y NFL Loser Pool - $wn Reminder";
            if ($send && $bcc) {
                mail($from, $subject, $body, "From: $from\r\nReply-To: $from\r\nBcc: $bcc\r\nX-Mailer: PHP/" . phpversion());
            }
            if (isSuperadmin()) {
                echo "Week $w, $y<br />";
                echo ($send ? 'SENT TO: ' : 'WOULD HAVE SENT TO: ') . "$bcc<br />";
                echo "<hr /><strong>$subject</strong><br /><br />$body";
            }
            
            // send reminder to hardcore players
            $subject = "$y NFL Hardcore Loser Pool - $wn Reminder";
            if ($send && $bcch) {
                mail($from, $subject, $body, "From: $from\r\nReply-To: $from\r\nBcc: $bcch\r\nX-Mailer: PHP/" . phpversion());
            }
            if (isSuperadmin()) {
                echo "Week $w, $y<br />";
                echo ($send ? 'SENT TO: ' : 'WOULD HAVE SENT TO: ') . "$bcch<br />";
                echo "<hr /><strong>$subject</strong><br /><br />$body";
            }
        }
    }
    
    public function actionAutoselect() {
        $y    = getCurrentYear();
        $w    = getCurrentWeek();
        
        if ($w > 1 && $w < 22) {
            if (isLocked($w)) {
                
                // normal mode
            	$sql = "delete from loserpick where yr = $y and week = $w and (teamid = 0 or teamid is null) and hardcore = 0";
                Yii::app()->db->createCommand($sql)->query();
                if (isSuperadmin()) {
                    echo "RAN:<br />$sql<br /><br />";
                }

                $sql = "insert into loserpick (userid, week, yr, teamid, hardcore, incorrect, setbysystem)
                        (select userid, $w, $y, teamid, 0, null, 1 from loserpick where yr = $y and week = " . ($w-1) . " and hardcore = 0 and not exists (
                            select * from loserpick l2 where l2.yr = $y and l2.week = $w and l2.userid = loserpick.userid and l2.teamid > 0 and l2.hardcore = 0
                        ))";
                Yii::app()->db->createCommand($sql)->query();
                if (isSuperadmin()) {
                    echo "RAN:<br />$sql";
                    exit;
                }
                
                // hardcore mode
                $sql = "delete from loserpick where yr = $y and week = $w and (teamid = 0 or teamid is null) and hardcore = 1";
                Yii::app()->db->createCommand($sql)->query();
                if (isSuperadmin()) {
                    echo "RAN:<br />$sql<br /><br />";
                }
                
/*                $sql = "insert into loserpick (userid, week, yr, teamid, hardcore, incorrect, setbysystem)
                        (select userid, $w, $y, 0, 1, 1, 1 from loserpick where yr = $y and week = " . ($w-1) . " and hardcore=1)";
                        */
                $sql = "insert into loserpick (userid, week, yr, teamid, hardcore, incorrect, setbysystem)
                        (select userid, $w, $y, teamid, 1, 1, 1 from loserpick where yr = $y and week = " . ($w-1) . " and hardcore = 1 and not exists (
                            select * from loserpick l2 where l2.yr = $y and l2.week = $w and l2.userid = loserpick.userid and l2.teamid > 0 and l2.hardcore = 1
                        ))";
                Yii::app()->db->createCommand($sql)->query();
                if (isSuperadmin()) {
                    echo "RAN:<br />$sql";
                    exit;
                }
            }
        }
        if (isSuperadmin()) {
            echo 'Nothing to run.';
        }
    }
    
}
