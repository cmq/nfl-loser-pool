<?php
/**
 * site-wide settings
 * 
 * acces with param('$KEY')
 */
$centralTimezone = new DateTimeZone('America/Chicago');
$easternTimezone = new DateTimeZone('America/New_York');

// this needs to be accessed via globals
$GLOBALS['firstGame'] = isProduction() ? array(  // KDHTODO change this once everything's up and running
    // Enter the times in EASTERN time
    1  => new DateTime('2014-09-04 20:30', $easternTimezone),
    2  => new DateTime('2014-09-11 20:25', $easternTimezone),
    3  => new DateTime('2014-09-18 20:25', $easternTimezone),
    4  => new DateTime('2014-09-25 20:25', $easternTimezone),
    5  => new DateTime('2014-10-02 20:25', $easternTimezone),
    6  => new DateTime('2014-10-09 20:25', $easternTimezone),
    7  => new DateTime('2014-10-16 20:25', $easternTimezone),
    8  => new DateTime('2014-10-23 20:25', $easternTimezone),
    9  => new DateTime('2014-10-30 20:25', $easternTimezone),
    10 => new DateTime('2014-11-06 20:25', $easternTimezone),
    11 => new DateTime('2014-11-13 20:25', $easternTimezone),
    12 => new DateTime('2014-11-20 20:25', $easternTimezone),
    13 => new DateTime('2014-11-27 12:30', $easternTimezone),
    14 => new DateTime('2014-12-04 20:25', $easternTimezone),
    15 => new DateTime('2014-12-11 20:25', $easternTimezone),
    16 => new DateTime('2014-12-18 20:25', $easternTimezone),
    17 => new DateTime('2014-12-28 13:00', $easternTimezone),
    18 => new DateTime('2015-01-03 16:30', $easternTimezone),	// Wild Card
    19 => new DateTime('2015-01-10 16:30', $easternTimezone),	// Quarter Finals
    20 => new DateTime('2015-01-18 15:00', $easternTimezone),	// Conference Championship
    21 => new DateTime('2015-02-01 18:30', $easternTimezone),	// Superbowl
) : array(
    // Enter the times in EASTERN time
    1  => new DateTime('2013-09-05 19:30', $easternTimezone),
    2  => new DateTime('2013-09-12 19:25', $easternTimezone),
    3  => new DateTime('2013-09-19 19:25', $easternTimezone),
    4  => new DateTime('2013-09-26 19:25', $easternTimezone),
    5  => new DateTime('2013-10-03 19:25', $easternTimezone),
    6  => new DateTime('2013-10-10 19:25', $easternTimezone),
    7  => new DateTime('2013-10-17 19:25', $easternTimezone),
    8  => new DateTime('2013-10-24 19:25', $easternTimezone),
    9  => new DateTime('2013-10-31 19:25', $easternTimezone),
    10 => new DateTime('2013-11-07 19:25', $easternTimezone),
    11 => new DateTime('2013-11-14 19:25', $easternTimezone),
    12 => new DateTime('2013-11-21 19:25', $easternTimezone),
    13 => new DateTime('2013-11-28 11:30', $easternTimezone),
    14 => new DateTime('2013-12-05 19:25', $easternTimezone),
    15 => new DateTime('2013-12-12 19:25', $easternTimezone),
    16 => new DateTime('2013-12-22 12:00', $easternTimezone),
    17 => new DateTime('2013-12-29 12:00', $easternTimezone),
    18 => new DateTime('2014-01-04 15:30', $easternTimezone),	// Wild Card
    19 => new DateTime('2014-01-11 15:30', $easternTimezone),	// Quarter Finals
    20 => new DateTime('2014-01-19 14:00', $easternTimezone),	// Conference Championship
    21 => new DateTime('2014-02-02 17:30', $easternTimezone),	// Superbowl
);


return array(
    'production'            => isProduction(),
    'currentYear'           => isProduction() ? 2014 : 2013,    // KDHTODO change this once everything's up and running
    'currentWeek'           => getCurrentWeek(),
    'headerWeek'            => getHeaderWeek(),
    'earliestYear'          => 2004,
    'adminEmail'            => 'kirk.hemmen@gmail.com',
    'systemEmail'           => 'kirk@loserpool.kdhstuff.com',
    'firstYearEntryFee'     => 10,
    'entryFee'              => 20,
    'movFee'                => 5,
    'movFirstYear'          => 2013,
    'firstGame'             => $GLOBALS['firstGame'],
    'id'                    => array(
        'statGroup' => array(
            'season' => 1,
            'picks'  => 3,
            'social' => 6,
        ),
        'stat'      => array(
            'picksTotal'                => 5,
            'picksCorrect'              => 8,
            'picksIncorrect'            => 9,
            'picksManual'               => 6,
            'picksSetBySystem'          => 7,
            'picksCorrectManual'        => 10,
            'picksIncorrectManual'      => 11,
            'picksCorrectSetBySystem'   => 12,
            'picksIncorrectSetBySystem' => 13,
            'rateCorrect'               => 16,
            'rateIncorrect'             => 17,
            'rateManual'                => 14,
            'rateSetBySystem'           => 15,
            'rateCorrectManual'         => 18,
            'rateIncorrectManual'       => 19,
            'rateCorrectSetBySystem'    => 20,
            'rateIncorrectSetBySystem'  => 21,
            'currentStreak'             => 30,
        ),
    ),
    'avatarExtensions'      => array('jpg', 'jpeg', 'gif', 'png'),
    'avatarSizeLimit'       => 1024 * 1024,    // 1MB limit
    'avatarMaxWidth'        => 40,
    'avatarMaxHeight'       => 40,
    'avatarDirectory'       => getcwd() . DIRECTORY_SEPARATOR  . 'images' . DIRECTORY_SEPARATOR  . 'avatar',
    'avatarWebDirectory'    => '/images/avatar',
    'winnerTrophyUrlPrefix' => '/images/badges/winnerbadge-',
    'boardPollerInterval'   => 1000 * 60 * 10,  // poll every 10 minutes
    'powerMultipliers'      => array(
        'pointsPerFirstPlace'   => 25,
        'pointsPerSecondPlace'  => 10,
        'pointsPerSeason'       => 4,
        'pointsPerWin'          => 1,
        'pointsPerDollar'       => .25,
        'winPctRampUp'          => 50,      // after 50 picks, the user's win percentage fully applies, until then it's ramping up
        'winPctThreshold'       => 50,      // if the user's win percentage is below this number, it will actually SUBTRACT from their power points
        'winPctMultiplier'      => 3,       // the user's win percentage (minus the winPctThreshold) is multiplied by this
        'movPoints'             => 6,       // points per average margin of defeat
        'pointsPerSetBySystem'  => -.5,     // the user loses this many points for every pick set by the system
        'pointsPerTalk'         => .4,
        'pointsPerLikesBy'      => .1, 
        'pointsPerLikesAt'      => .2,
        'pointsPerReferral'     => 5,
    ),
);