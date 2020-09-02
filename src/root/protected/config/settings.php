<?php
/**
 * site-wide settings
 *
 * acces with param('$KEY')
 */
$centralTimezone = new DateTimeZone('America/Chicago');
$easternTimezone = new DateTimeZone('America/New_York');

// this needs to be accessed via globals
$GLOBALS['firstGame'] = array(
    // Enter the times in EASTERN time
    1  => new DateTime('2020-09-10 20:20', $easternTimezone),
    2  => new DateTime('2020-09-17 20:20', $easternTimezone),
    3  => new DateTime('2020-09-24 20:20', $easternTimezone),
    4  => new DateTime('2020-10-01 20:20', $easternTimezone),
    5  => new DateTime('2020-10-08 20:20', $easternTimezone),
    6  => new DateTime('2020-10-15 20:20', $easternTimezone),
    7  => new DateTime('2020-10-22 20:20', $easternTimezone),
    8  => new DateTime('2020-10-29 20:20', $easternTimezone),
    9  => new DateTime('2020-11-05 20:20', $easternTimezone),
    10 => new DateTime('2020-11-12 20:20', $easternTimezone),
    11 => new DateTime('2020-11-19 20:20', $easternTimezone),
    12 => new DateTime('2020-11-26 12:30', $easternTimezone),
    13 => new DateTime('2020-12-03 20:20', $easternTimezone),
    14 => new DateTime('2020-12-10 20:20', $easternTimezone),
    15 => new DateTime('2020-12-17 20:20', $easternTimezone),
    16 => new DateTime('2020-12-25 16:30', $easternTimezone),
    17 => new DateTime('2021-01-03 13:00', $easternTimezone),
    18 => new DateTime('2021-01-09 16:30', $easternTimezone),   // Wild Card
    19 => new DateTime('2021-01-16 16:30', $easternTimezone),   // Quarter Finals
    20 => new DateTime('2021-01-25 15:05', $easternTimezone),   // Conference Championship
    21 => new DateTime('2021-02-07 18:30', $easternTimezone),   // Superbowl
);


return array(
    'production'            => isProduction(),
    'currentYear'           => 2020,
    'currentWeek'           => getCurrentWeek(),
    'headerWeek'            => getHeaderWeek(),
    'earliestYear'          => 2004,
    'earliestYearHardcore'  => 2020,
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
