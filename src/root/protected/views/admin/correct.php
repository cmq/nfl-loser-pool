<?php
foreach ($picks as $pick) {
    echo $pick->user->username . ': ' . $pick->team->longname . '<br />';
}
echo '<br />';
foreach ($movs as $mov) {
    echo $mov->team->longname . ': ' . $mov->mov . '<br />';
}