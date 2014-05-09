<?php
// KDHTODO add searching?
// KDHTODO show more user details (avatar, badges, trophies, etc)
// KDHTODO allow order by power ranking
// KDHTODO allow hiding of inactive players
// KDHTODO have a place to show details of how the power ranking was calculated

if (count($users)) {
    foreach ($users as $user) {
        echo getProfileLink($user) . '<br />';
    }
} else {
    echo 'No users found.';
}