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
    $keys_folder    = dirname(__FILE__) . '/keys'; # Relative path to keys folder.
    $keys_files     = glob("$keys_folder/*.php");  # Get all files in the keys folder.
    $count          = count($keys_files);          # Count the number of files in the keys folder.

    if (empty($keys_files)) {
        die("No keys files found in keys folder.");
    }

    $excludes = [
        $keys_folder."/my_custom_keys.php",
    ];
    $count_excludes = count($excludes);
    
    if ($count == $count_excludes) {
        require_once($keys_folder."/my_custom_keys.php");
    } elseif ($count > $count_excludes) {
        foreach (glob($keys_folder."/*.php") as $file) {
            if (!in_array($file, $excludes)) {
                require_once($file);
            }
        }
    }

    if (!isset($apikeys) || empty($apikeys)) {
        die("Variable \$apikeys not set. Please check your settings (or more specifically your keys folder).");
    }

    define('API_KEYS', $apikeys);
    break;

    die("Something went wrong while loading keys files.");
    
} while (False);
?>