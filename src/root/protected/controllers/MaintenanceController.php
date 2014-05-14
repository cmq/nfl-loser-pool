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
</ul>
*/
class MaintenanceController extends Controller
{
    
    private $reverseStats = null;
    private $users = array();
    
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
        					and p.incorrect is not null
        		inner join	mov on p.teamid = mov.teamid
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
            $user['years'][$y]['weeks'][$w]['streak'] = $currentStreak;
            $user['currentStreak']                    = $currentStreak;
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
        
        
        // number of badges for each user (also by year)
        // NOTE:  This does not include badges that are floating or can be lost like the .800 badge -- those are calculated and added to these totals in the recalcBadges() method
        $sql = 'select ub.userid, b.id, ub.yr, b.unlocked_year from badge b inner join userbadge ub on ub.badgeid = b.id where b.unlocked_year is not null';
        $rsBadge = Yii::app()->db->createCommand($sql)->query();
        foreach ($rsBadge as $row) {
            if (array_key_exists($row['userid'], $users)) {
                $users[$row['userid']]['badges'][] = $row['id'];
                if ($row['yr']) {
                    $users[$row['userid']]['years'][$row['yr']]['badges'][] = $row['id'];
                }
            }
        }
        
        
        // KDHTODO (later)
        //<li>Times on Bandwagon</li>
        //<li>Times Off Bandwagon</li>
        //<li>Times Dodging a Bandwagon Crash</li>
        //<li>Times Being Bandwagon Chief</li>
        
        // KDHTODO (later)
        //<li>Current Power Ranking</li>
        //<li>Highest Power Ranking</li>
        //<li>Lowest Power Ranking</li>
        //<li>Largest One-Week Power Ranking Jump</li>
        
        
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
            // KDHTODO more things to do here
        }

        // set the internal users array
        $this->users = $users;
        
        // recalculate the floating and losable badges
        $this->_recalcBadges();
        
        // recalculate the power ranking
        // - populates $user['powerrank']
        $this->_recalcPower();
        
        // empty out any previous userstat values
        $sql = 'delete from userstat';
        $rs = Yii::app()->db->createCommand($sql)->query();
        
        // insert all the new values
        foreach ($this->STATS as $s=>$stat) {
            $this->_insertStat($s);
        }
    }
    
    private function _recalcBadges() {
        // KDHTODO calculate who currently has each badge
        // KDHTODO calculate who had each badge at the end of each year, too (so who had the current streak at the end of every year, etc)
        // KDHTODO Note that some badges have introduction years, so don't retroactively apply them to users in earlier years
        
        // list of badges that need to be calculated on-the-fly, not just using the userbadge table:
        // 12 - monkey on my back
        // 13 - on fire
        // 14 - all-time on fire
        // 16 - total defeat
        // 18 - .800 club
        
        // loop over years and calculate who HAD these at each given year, and also who has them now
        
    }
    
    private function _recalcPower() {
        // KDHTODO allow calc of every week of every year so entire power history is available
            // this would require knowing which/how many badges they had AT THE TIME
            // No it wouldn't -- we're going to make a rule that points from badges/talks/likes only come at the end of the year
        foreach ($this->users as &$user) {
            // KDHTODO figure this out for real
            $user['powerrank'] = $user['id'];
        }
    }
    
    public function actionIndex() {
        exit;
    }
    
    public function actionRecalc() {
        /*
        $userId = (int) getRequestParameter('uid', 0);
        $year   = (int) getRequestParameter('year', 0);
        $week   = (int) getRequestParameter('week', 0);
        */
        
        $this->_recalcStats();
        echo 'done.';
    }
}