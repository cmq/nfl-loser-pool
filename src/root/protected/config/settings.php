<?php
/**
 * site-wide settings
 * 
 * acces with param('$KEY')
 */

return array(
    'currentYear'           => 2013,
    'currentWeek'           => getCurrentWeek(),
    'headerWeek'            => getHeaderWeek(),
    'earliestYear'          => 2004,
    'adminEmail'            => 'kirk.hemmen@gmail.com',
    'firstGame'             => array(
        1  => mktime(19, 30, 0, 9, 5, 2013),
        2  => mktime(19, 25, 0, 9, 12, 2013),
        3  => mktime(19, 25, 0, 9, 19, 2013),
        4  => mktime(19, 25, 0, 9, 26, 2013),
        5  => mktime(19, 25, 0, 10, 3, 2013),
        6  => mktime(19, 25, 0, 10, 10, 2013),
        7  => mktime(19, 25, 0, 10, 17, 2013),
        8  => mktime(19, 25, 0, 10, 24, 2013),
        9  => mktime(19, 25, 0, 10, 31, 2013),
        10 => mktime(19, 25, 0, 11, 7, 2013),
        11 => mktime(19, 25, 0, 11, 14, 2013),
        12 => mktime(19, 25, 0, 11, 21, 2013),
        13 => mktime(11, 30, 0, 11, 28, 2013),
        14 => mktime(19, 25, 0, 12, 5, 2013),
        15 => mktime(19, 25, 0, 12, 12, 2013),
        16 => mktime(12, 0, 0, 12, 22, 2013),
        17 => mktime(12, 0, 0, 12, 29, 2013),
        18 => mktime(15, 30, 0, 1, 4, 2014),	// Wild Card
        19 => mktime(15, 30, 0, 1, 11, 2014),	// Quarter Finals
        20 => mktime(14, 0, 0, 1, 19, 2014),	// Conference Championship
        21 => mktime(17, 30, 0, 2, 2, 2014),	// Superbowl
    ),
    'avatarExtensions'      => array('jpg', 'jpeg', 'gif', 'png'),
    'avatarSizeLimit'       => 1024 * 1024,    // 1MB limit
    'avatarMaxWidth'        => 40,
    'avatarMaxHeight'       => 40,
    'avatarDirectory'       => getcwd() . DIRECTORY_SEPARATOR  . 'images' . DIRECTORY_SEPARATOR  . 'avatar',
    'avatarWebDirectory'    => '/images/avatar',
);