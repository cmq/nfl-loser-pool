<?php
// KDHTODO add searching?
// KDHTODO show more user details (avatar, badges, trophies, etc)

if (count($users)) {
    foreach ($users as $user) {
        echo getProfileLink($user) . '<br />';
    }
} else {
    echo 'No users found.';
}