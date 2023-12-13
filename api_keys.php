<?php
/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_keys.php                             */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/*
    # This file should contain the default values for everything in keys -
    # and it should be applied if the constants are not defined, which could cause an error.

    # NOTE: Please do not change this file directly, change the values in
    #       the 'keys' folder instead.
*/

if (defined('API_KEYS')) {
    die("
        API_KEYS is already defined. Please check your settings.
        It should NOT be defined in any of the files under your 'settings' folder, only in api_keys.php.
    ");
}

do {
    # Check if keys folder contains custom configuration files.
    $keys_files = glob("keys/*.php");   # Get all files in the keys folder.
    $count          = count($keys_files);   # Count the number of files in the keys folder.

    if (empty($keys_files)) {
        die("No keys files found in keys folder.");
    }

    $excludes = [
        "keys/my_custom_keys.php",
    ];
    $count_excludes = count($excludes);
    
    if ($count == $count_excludes) {
        require_once("keys/my_custom_keys.php");
        break;
    }
    
    if ($count > $count_excludes) {
        foreach (glob("keys/*.php") as $file) {
            if (!in_array($file, $excludes)) {
                require_once($file);
            }
        }
        break;
    }

    die("Something went wrong while loading keys files.");
    
} while (False);
?>