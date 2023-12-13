<?php
/* ────────────────────────────────────────────────────────────────────────── */
/*                               api_includes.php                             */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/*

    If you have a file named custom_[anything from $include].php,
    the custom file will be included instead of the main file.

    I did this mostly because I needed to test stuff for myself without git pushing.

*/

try {
    $includes = [
        'api_base',
        'api_endpoints',
        'api_keys',
        'api_aliases',
    ];

    foreach ($includes as $include) {

        if (!is_file($include.'.php')) {
            die("Failed to include required file $include<br>");
        }

        require_once($include.'.php');
    }
} catch (Exception $e) {
    die($e->getMessage());
}
?>