<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_aliases.php                           */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/*
    # This file should contain the default values for everything in aliases -
    # and it should be applied if the constants are not defined, which could cause an error.

    # NOTE: Please do not change this file directly, change the values in
    #       the 'aliases' folder instead.
*/

do {
    # Check if aliases folder contains custom configuration files.
    $aliases_folder   = dirname(__FILE__) . '/aliases';  # Relative path to aliases folder.
    $aliases_files    = glob("$aliases_folder/*.php");   # Get all files in the aliases folder.
    $count            = count($aliases_files);           # Count the number of files in the aliases folder.

    if (empty($aliases_files)) {
        die("No aliases files found in aliases folder.");
    }

    $excludes = [
        "aliases/my_custom_aliases.php",
    ];
    $count_excludes = count($excludes);
    
    if ($count == $count_excludes) {
        require_once("aliases/my_custom_aliases.php");
        break;
    }
    
    if ($count > $count_excludes) {
        foreach (glob("aliases/*.php") as $file) {
            if (!in_array($file, $excludes)) {
                require_once($file);
            }
        }
        break;
    }

    die("Something went wrong while loading endpoints files.");
    
} while (False);
?>