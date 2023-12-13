<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_settings.php                         */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/*
    Here you can make customizations to your API.
    It contains some default values that could be tweaked.
*/

/* ───────────────────────────────────────────────────────────────────── */
/*                         NOTE: Edit 2023-11-30:                        */
/* ───────────────────────────────────────────────────────────────────── */
/*                                                                       */
/*    The defaults are now set in api_defaults.php                       */
/*    All we have to do here is include it, it will define               */
/*    constants that aren't already  defined by a custom settings file.  */
/* ───────────────────────────────────────────────────────────────────── */
if (!file_exists("api_defaults.php")) {
    die("api_defaults.php not found.");
}

include_once("api_defaults.php");
?>