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

        # Folder (will replace Custom)
        $folder = explode("_", $include)[1];

        # Custom (will replace Default)
        $custom = 'custom_'.$include.'.php';

        # Default
        $default = $include.'.php';

        # Directory with this prefix will be included
        if (is_dir($folder)) {
            foreach (glob($folder."/*.php") as $file) {
                require_once($file);
            }
            # We don't continue here because we want to include the custom file if it exists as well
            # Actually, we might want to...
            continue;
        } 
        
        # The custom file will be included instead of the default file
        if (file_exists($custom)) {
            define('INCLUDE_'.strtoupper($include), $custom);
            require_once($custom);
            continue;
        } 
        
        # The default file will be included
        if (file_exists($default)) {
            define('INCLUDE_'.strtoupper($include), $default);
            require_once($default);
            continue;
        }

        die("Failed to include $include<br>");
    }
} catch (Exception $e) {
    die($e->getMessage());
}
?>