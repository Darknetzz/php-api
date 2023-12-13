<?php
/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_keys.php                             */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/*
    # NOTE: Please do not put API keys here.
    Define your API keys in the 'keys' folder instead.
*/

if (defined('API_KEYS')) {
    die("
        API_KEYS is already defined. Please check your settings.
        It should NOT be defined in any of the files under your 'settings' folder, only in api_keys.php.
    ");
}

foreach (glob("keys/*.php") as $file) {
    require_once($file);
}

define("API_KEYS", $apikeys);
?>
