<?php
// KDHTODO give an edit link to take them to their "edit profile" page
if ($user) {
    echo 'Profile for ' . $user->username;
} else {
    'That user was not found.';
}