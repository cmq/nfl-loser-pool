<?php
function isProduction() {
    return (strstr(strtolower($_SERVER['HTTP_HOST']), 'loserpool.kdhstuff.com') !== false);
}

function isAdmin() {
    return (bool) userField('admin');
}

function isSuperadmin() {
    return (bool) userField('superadmin');
}

function isPaid($hardcore=null) {
    return true;
    $user = user();
    $hardcore = is_null($hardcore) ? isHardcoreMode() : (bool) $hardcore;
    $thisYear = $user ? ($hardcore ? $user->thisYearHardcore : $user->thisYearNormal) : null;
    return $thisYear ? (bool) $thisYear->paid : false;
}

function isHardcoreMode() {
    return (isset($_SESSION['mode']) && $_SESSION['mode'] == 'hardcore');
}

function isNormalMode() {
    return !isHardcoreMode();
}

function userHasHardcoreMode() {
    $user = user();
    return $user ? ($user->thisYearHardcore ? true : false) : false;
}

function userHasNormalMode() {
    $user = user();
    return $user ? ($user->thisYearNormal ? true : false) : false;
}

function getCurrentYear() {
    return param('currentYear');
}

function getCurrentWeek() {
    $currentWeek = param('currentWeek');
    if ($currentWeek) {
        return $currentWeek;
    }
    
    $dInOneHour  = new DateTime('NOW');
    $firstGames  = $GLOBALS['firstGame'];
    $currentWeek = 0;
    
    $dInOneHour->add(new DateInterval('PT1H'));
    for ($i=1; $i<=21; $i++) {
    	if ($dInOneHour > $firstGames[$i]) {
    		// in one hour, the game of week $i will have started
    		$currentWeek = $i;
    	}
    }
    return $currentWeek;
}

function getHeaderWeek() {
    $headerWeek = param('headerWeek');
    if ($headerWeek) {
        return $headerWeek;
    }
    
    $dNow        = new DateTime('NOW');
    $dIn5Days    = new DateTime('NOW');
    $dIn12Days   = new DateTime('NOW');
    $firstGames  = $GLOBALS['firstGame'];
    $headerWeek = 0;
    
    $dIn5Days->add(new DateInterval('P5D'));
    $dIn12Days->add(new DateInterval('P12D'));
    
    for ($i=1; $i<=21; $i++) {
    	if ($dIn5Days > $firstGames[$i]) {
    	    // the first game of week $i is within 5 days
    		$headerWeek = $i;
    	} else if ($i > 20 && $dIn12Days > $firstGames[$i]) {
    		// otherwise, a special case for the superbowl week... if we're within 12 days
    		$headerWeek = $i;
    	}
    }
    $headerWeek = min(max(1, $headerWeek), 21);
    if ($headerWeek < 18 && $firstGames[$headerWeek]->format('w') > 1) {
    	// the first game of the header week is on a Tuesday-Saturday (i.e., not Sunday) -- if the current day is not yet Tuesday,
    	// continue to show the previous week in the header, because even though we're within 5 days of the next week's games starting,
    	// the current week is still going on!
    	// Note that this doesn't apply during the playoffs
    	if ($dNow->format('w') < 2) {
    		$headerWeek--;
    	}
    }
    return $headerWeek;
}

function getWeekName($week, $label=false) {
	$name = $week;
	switch ($week) {
		case 18:
			$name = 'Wild Card' . ($label ? ' Week' : '');
			break;
		case 19:
			$name = 'Divisional' . ($label ? ' Week' : '');
			break;
		case 20:
			$name = 'Conf Champ' . ($label ? ' Week' : '');
			break;
		case 21:
			$name = ($label ? 'The ' : '') . 'Superbowl';
			break;
		default:
			$name = ($label ? 'Week ' : '') . $week;
			break;
	}
	return $name;
}

function isLocked($week) {
    $now      = new DateTime();
    $locktime = getLockTime($week);
    return ($now >= $locktime);
}

function getLockTime($week, $format=false) {
    $firstGames = param('firstGame');
    $locktime = clone $firstGames[$week];
    $locktime->sub(new DateInterval('PT1H'));   // lock 1 hour before the start
    if ($format) {
        modifyTimeForUser($locktime);
        return $locktime->format('m/d/y h:i a');
    }
    return $locktime;
}

function modifyTimeForUser(&$datetime) {
    // figure out how the user's timezone applies
    // NOTE:  locktime comes back in eastern time, but our user timezone modifications are based on central time, so start with -1
    $hours = -1;
    if (userField('timezone')) {
        $hours = (int) userField('timezone');
    }
    // figure out how the user's dst settings apply
    if (date('I')) {
        // it is currently daylight savings time
        if (!userField('use_dst')) {
            // but the user doesn't want to use daylight savings time
            $hours -= 1;
        }
    }
    if ($hours > 0) {
        $datetime->add(new DateInterval("PT{$hours}H"));
    } else if ($hours < 0) {
        $hours *= -1;
        $datetime->sub(new DateInterval("PT{$hours}H"));
    }
}

function formatDateTimeForUserTimezone($datetime, $format='g:ia \o\n D, M jS, Y') {
    // adjust the posted time for the user's timezone
    // NOTE:  locktime comes back in mountain time, but our user timezone modifications are based on central time, so start with +1
    $hours = 1;
    if (userField('timezone')) {
        $hours += (int) userField('timezone');
    }
    // figure out how the user's dst settings apply
    if (date('I')) {
        // it is currently daylight savings time
        if (!userField('use_dst')) {
            // but the user doesn't want to use daylight savings time
            $hours -= 1;
        }
    }
    if ($hours > 0) {
        $datetime->add(new DateInterval("PT{$hours}H"));
    } else if ($hours < 0) {
        $hours *= -1;
        $datetime->sub(new DateInterval("PT{$hours}H"));
    }
    return $datetime->format($format);
}

function createThumbnail($source, $destination, $maxWidth, $maxHeight) {
    // tried to copy this from http://mediumexposure.com/smart-image-resizing-while-preserving-transparency-php-and-gd-library/ but
    // kept having problems with .gif transparencies.... copied their function instead and it seems to work, too tired to figure
    // out what the difference is
    try {
        $imageinfo = getimagesize($source);
        $ext = $imageinfo[2];

        // create a new source image resource
        $sourceImage = null;
        switch ($ext) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($source);
                imagealphablending($sourceImage, true);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($source);
                imagealphablending($sourceImage, true);
                break;
        }

        if ($sourceImage) {
            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);
            $sourceRatio = $sourceWidth/$sourceHeight;

            // determine the dimensions of the target image
            if ($maxWidth >= $sourceWidth && $maxHeight >= $sourceHeight) {
                // no need to resize
                $targetWidth = $sourceWidth;
                $targetHeight = $sourceHeight;
            } else {
                $targetRatio = $maxWidth/$maxHeight;
                if ($sourceRatio > $targetRatio) {
                    // source is too wide, maximize width and scale height to match
                    $targetWidth = $maxWidth;
                    $targetHeight = (int) $targetWidth/$sourceRatio;
                } else {
                    // source is too tall (or just right), maximize height and scale width to match
                    $targetHeight = $maxHeight;
                    $targetWidth = (int) $targetHeight*$sourceRatio;
                }
            }

            // create the target image
            $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

            // handle transparency
            if ($ext == IMAGETYPE_GIF || $ext == IMAGETYPE_PNG) {
                $transparentIndex = imagecolortransparent($sourceImage);
                if (false) {    /* This method was copied from several places on the internet, but doesn't work for all gif files
                    if ($transparentIndex >= 0) {*/
                    // we have a specific transparent color
                    $palletsize = imagecolorstotal($sourceImage);
                    if ($palletsize > $transparentIndex) {
                        // get the original image's transparent color's RGB values
                        $transparentColor = imagecolorsforindex($sourceImage, $transparentIndex);
                        // allocate the same color in the new image resource
                        $transparentIndex = imagecolorallocatealpha($targetImage, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue'], 127);
                    } else {
                        // got an "index out of range" error on a file I tested... try this instead
                        $transparentIndex = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
                    }
                    // completely fill the background of the new image with the allocated color
                    imagefill($targetImage, 0, 0, $transparentIndex);
                    // set the background color for the new image to transparent
                    imagecolortransparent($targetImage, $transparentIndex);
                    // try again to set the background to transparent, pixel by pixel
                    for ($x=0; $x<$targetWidth; $x++) {
                        for ($y=0; $y<$targetHeight; $y++) {
                            imagesetpixel($targetImage, $x, $y, $transparentIndex);
                        }
                    }
                } else {    /* The above method was copied from several places on the internet, but doesn't work for all gif files, so we'll use the below in all cases instead
                    } elseif ($ext == IMAGETYPE_PNG) {*/
                    // for PNGs without a default transparency, make one
                    // temporarily turn off transparency blending
                    imagealphablending($targetImage, false);
                    // create a new transparent color for the image
                    $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
                    // completely fill the background of the new image with the allocated color
                    imagefill($targetImage, 0, 0, $transparent);
                    // restore transparency blending
                    imagesavealpha($targetImage, true);
                }
            }

            // create the target image
            imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
            switch ($ext) {
                case IMAGETYPE_JPEG:
                    imagejpeg($targetImage, $destination);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($targetImage, $destination);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($targetImage, $destination);
                    break;
            }

        }
    } catch (Exception $e) {
        return false;
    }
    return true;
}


function getUserAvatar($userid, $ext='png', $linkToFull=true, $extraClasses='') {
    if ($userid instanceof User) {
        $ext    = $userid->avatar_ext;
        $userid = $userid->id;
    }
    $url  = getUserAvatarUrl($userid, $ext);
    $turl = getUserAvatarUrl($userid, $ext, true);
    if ($linkToFull) {
        return "<a href=\"$url?x=" . time() . "\" class=\"avatar $extraClasses\" id=\"avatar$userid\"><img class=\"avatar\" src=\"$turl\" /></a>";
    } else {
        return "<img class=\"avatar $extraClasses\" src=\"$turl\" />";
    }
}


function getUserAvatarUrl($userid, $ext, $thumb=false) {
    if (!$ext) {
        $userid = 0;
        $ext = 'png';
    }
    return param('avatarWebDirectory') . '/' . ($thumb ? 't' : '') . "$userid.$ext";
}

function getLiveScoring($week=null) {
    if (!$week) {
        $week = getCurrentWeek();
    }
    $scoresFinal = null;
    /*
    // Apparently nfl.com changed their API for the 2018 season.  Here was the old way
    try {
        if ($week <= 17) {
            $scoreJson = file_get_contents('http://www.nfl.com/liveupdate/scorestrip/scorestrip.json?random=' . rand(10000000, 99999999));
            $lastLen = -1;
            while (strlen($scoreJson) != $lastLen) {
                $lastLen = strlen($scoreJson);
                $scoreJson = preg_replace('/,,/', ',null,', $scoreJson);
            }
            $scoresRaw = json_decode($scoreJson);
            $scoresFinal = array();
            foreach ($scoresRaw->ss as $score) {
                if (substr(strtolower($score[2]), 0, 7) != 'pregame') {
                    $scoresFinal[] = array(
                        'awayteam'  => $score[4],
                        'awayscore' => (int) $score[5],
                        'awaymov'   => ((int) $score[5] - (int) $score[7]),
                        'hometeam'  => $score[6],
                        'homescore' => (int) $score[7],
                        'homemov'   => ((int) $score[7] - (int) $score[5]),
                    	'final'     => (substr(strtolower($score[2]), 0, 5) == 'final')
                    );
                }
            }
            if (count($scoresFinal) === 0) {
                $bShowScores = false;
            }
        } else {
            $scoreJson = file_get_contents('http://www.nfl.com/liveupdate/scores/scores.json?random=' . rand(10000000, 99999999));
            $scoresRaw = json_decode($scoreJson, true);
            $scoresFinal = array();
            foreach ($scoresRaw as $score) {
                $scoresFinal[] = array(
                    'awayteam'  => $score['away']['abbr'],
                    'awayscore' => (int) $score['away']['score']['T'],
                    'awaymov'   => ((int) $score['away']['score']['T'] - (int) $score['home']['score']['T']),
                    'hometeam'  => $score['home']['abbr'],
                    'homescore' => (int) $score['home']['score']['T'],
                    'homemov'   => ((int) $score['home']['score']['T'] - (int) $score['away']['score']['T']),
                	'final'     => (substr(strtolower($score['qtr']), 0, 5) == 'final')
                );
            }
        }
    } catch (Exception $e) {
        $scoresFinal = null;
    }
    */
    // Here's the new way as of 2018
    /* they broke it again
    try {
		$scoreJson = file_get_contents('https://feeds.nfl.com/feeds-rs/scores.json?random=' . rand(10000000, 99999999));
		$scoresRaw = json_decode($scoreJson, true);
		$scoresFinal = array();
		if ($scoresRaw['week'] == getCurrentWeek()) {
			foreach ($scoresRaw['gameScores'] as $score) {
				$hasScore = $score['score'] != null;
				$awayTeamScore = (int) $hasScore ? $score['score']['visitorTeamScore']['pointTotal'] : 0;
				$homeTeamScore = (int) $hasScore ? $score['score']['homeTeamScore']['pointTotal'] : 0;
				$scoresFinal[] = array(
					'awayteam'  => $score['gameSchedule']['visitorTeamAbbr'],
					'awayscore' => $awayTeamScore,
					'awaymov'   => $awayTeamScore - $homeTeamScore,
					'hometeam'  => $score['gameSchedule']['homeTeamAbbr'],
					'homescore' => $homeTeamScore,
					'homemov'   => $homeTeamScore - $awayTeamScore,
					'final'     => ($score['score']['phase'] == 'FINAL' || $score['score']['phase'] == 'FINAL_OVERTIME')
				);
			}
		}
    } catch (Exception $e) {
        $scoresFinal = null;
    }
    */
    return array(); // 2020 they broke the API
    
    return $scoresFinal;
}

function getProfileLink($userOrId, $username='') {
    // provide either an ID/name as two parameters, or a User object as a single parameter
    if ($userOrId instanceof User) {
        $userId   = $userOrId->id;
        $username = $userOrId->username;
    } else {
        $userId   = $userOrId;
    }
    return CHtml::link($username, array('stats/profile', 'id'=>$userId));
}

function getAvatarProfileLink($user, $withName=false, $small=false) {
    return getProfileLink($user->id, getUserAvatar($user->id, $user->avatar_ext, false, $small ? 'tiny-avatar' : '') . ($withName ? $user->username : ''));
}

function formatStat($value, $type) {
    $ret = $value;
    switch ($type) {
        case 'money':
            $ret = '$' . number_format((float) $value, 2);
            break;
        case 'int':
            $ret = (int) $value;
            break;
        case 'decimal':
            $ret = number_format((float) $value, 3);
            break;
        case 'percent':
            $ret = number_format((float) $value * 100, 2) . '%';
            break;
    }
    return $ret;
}

function getTeamLogoOffset($team, $size) {
    $multiplier = 50;
    $offset     = (isset($team['image_offset']) ? $team['image_offset'] : 0);
    if (strtolower($size) == 'large') {
        $multiplier = 80;
    }
    return '0 -' . ($multiplier * $offset) . 'px';
}

function getBestWorst($year)
{
    if ($year <= param('earliestYear')) {
        return null;
    }
    
    $sql = 'select mov.yr, mov.week, mov.teamid, mov.mov, t.shortname, t.longname, t.image_offset
                from mov
                inner join (
                    select yr, week, min(mov) as mov
                    from mov
                    group by yr, week
                ) as top on mov.yr= top.yr and mov.week = top.week and mov.mov = top.mov
                inner join loserteam t on mov.teamid = t.id
                where mov.yr = ' . (int)$year . '
                order by mov.yr, mov.week';
    $bestMov = Yii::app()->db->createCommand($sql)->queryAll();
    $sql = str_replace('min(', 'max(', $sql);
    $worstMov = Yii::app()->db->createCommand($sql)->queryAll();
    
    $bestWorst = array('best'=>array_fill(0, 21, null), 'worst'=>array_fill(0, 21, null));
    
    foreach ($bestMov as $game) {
        if ($game['mov'] < 0) {
            $bestWorst['best'][$game['week']-1] = array(
                'year'  => $game['yr'],
                'mov'   => $game['mov'],
                'team'=>array(
                    'id'            => $game['teamid'],
                    'shortname'     => $game['shortname'],
                    'longname'      => $game['longname'],
                    'image_offset'  => $game['image_offset']
                )
            );
        }
    }
    foreach ($worstMov as $game) {
        if ($game['mov'] > 0) {
            $bestWorst['worst'][$game['week']-1] = array(
                'year'  => $game['yr'],
                'mov'   => $game['mov'],
                'team'=>array(
                    'id'            => $game['teamid'],
                    'shortname'     => $game['shortname'],
                    'longname'      => $game['longname'],
                    'image_offset'  => $game['image_offset']
                )
            );
        }
    }
    
    return $bestWorst;
}
