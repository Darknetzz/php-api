<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_endpoints.php                         */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/*
    # This file should contain the default values for everything in endpoints -
    # and it should be applied if the constants are not defined, which could cause an error.

    # NOTE: Please do not change this file directly, change the values in
    #       the 'endpoints' folder instead.
*/

do {
    # Check if endpoints folder contains custom configuration files.
    $endpoints_folder   = dirname(__FILE__) . '/endpoints';  # Relative path to endpoints folder.
    $endpoints_files    = glob("$endpoints_folder/*.php");   # Get all files in the endpoints folder.
    $count              = count($endpoints_files);           # Count the number of files in the endpoints folder.

    if (empty($endpoints_files)) {
        die("No endpoints files found in endpoints folder.");
    }

    $excludes = [
        $endpoints_folder."/my_custom_endpoints.php",
    ];
    $count_excludes = count($excludes);
    
    if ($count == $count_excludes) {
        require_once($endpoints_folder."/my_custom_endpoints.php");
        $example_endpoints = dirname(__FILE__) . '/examples/endpoints_quote_funfact.php';
        if (is_file($example_endpoints)) {
            require_once($example_endpoints);
        }
        break;
    }
    
    if ($count > $count_excludes) {
        foreach (glob($endpoints_folder."/*.php") as $file) {
            if (!in_array($file, $excludes)) {
                require_once($file);
            }
        }

        $example_endpoints = dirname(__FILE__) . '/examples/endpoints_quote_funfact.php';
        if (is_file($example_endpoints)) {
            require_once($example_endpoints);
        }

        break;
    }

    die("Something went wrong while loading endpoints files.");
    
} while (False);
?>