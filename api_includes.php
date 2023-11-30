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

$includes = [
    'api_settings', # settings is already included in index.php
    'api_base',
    'api_endpoints',
    'api_keys',
    'api_aliases',
];

foreach ($includes as $include) {

    # Folder (will replace Custom)
    $folder = explode("_", $include)[1];

    # Custom (will replace Default)
    $custom = 'custom_'.$include.'.php';

    # Default
    $default = $include.'.php';

    if (is_dir($folder)) {
        foreach (glob($folder."/*.php") as $file) {
            require_once($file);
        }
    } elseif (file_exists($custom)) {
        define('INCLUDE_'.strtoupper($include), $custom);
        require_once($custom);
    } elseif (file_exists($default)) {
        define('INCLUDE_'.strtoupper($include), $default);
        require_once($default);
    } else {
        die("Failed to include $include<br>");
    }
}
?>