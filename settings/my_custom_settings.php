<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                             my_custom_settings.php                         */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/*
    This file should contain custom settings for your API.
    It contains some default values that could be tweaked.

    Take a look at 'default_settings.php' to see what can be tweaked.
*/
try {

    $customs = [
        # Put your custom settings here.
    ];

    foreach ($customs as $const => $val) {
        if (!defined($const)) {
            define($const, $val);
        }
    }

} catch (Exception $e) {
    die("Error loading custom settings: ".$e->getMessage());
}
?>