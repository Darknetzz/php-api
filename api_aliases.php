<?php
/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_aliases.php                          */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/*
    # This file should contain aliases
*/

do {
    # Check if folder contains custom configuration files.
    $alias_files    = glob("aliases/*.php");    # Get all files in the folder.
    $count          = count($alias_files);   # Count the number of files in the folder.

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

    die("Something went wrong while loading aliases files.");
    
} while (False);
?>