<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_settings.php                         */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/*
    # This file should contain the default values for everything in settings -
    # and it should be applied if the constants are not defined, which could cause an error.

    # NOTE: Please do not change this file directly, change the values in
    #       the 'settings' folder instead.
*/

do {
    # Check if settings folder contains custom configuration files.
    $settings_folder    = dirname(__FILE__) . '/settings';  # Relative path to settings folder.
    $settings_files     = glob("$settings_folder/*.php");   # Get all files in the settings folder.
    $count              = count($settings_files);           # Count the number of files in the settings folder.

    if (empty($settings_files)) {
        die("No settings files found in settings folder.");
    }

    $excludes       = [
        $settings_folder."/default_settings.php",
        $settings_folder."/custom_api_settings.php",
    ];
    
    if ($count > 2) {
        foreach (glob($settings_folder."/*.php") as $file) {
            if (!in_array($file, $excludes)) {
                require_once($file);
            }
        }
    }

    # Include defaults regardless, as it will only set the undefined settings
    require_once($settings_folder."/default_settings.php");

} while (False);
?>