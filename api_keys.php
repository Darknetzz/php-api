<?php
/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_keys.php                             */
/* ────────────────────────────────────────────────────────────────────────── */

if (defined('API_KEYS')) {
    die("
        API_KEYS is already defined. Please check your settings.
        It should NOT be defined in any of the files under your 'settings' folder, only in api_keys.php.
    ");
}

$apikeys = [];
$keyStore = getApiKeyStore();

if ($keyStore instanceof ApiKeyStore) {
    $apikeys = $keyStore->loadAll();
    if (empty($apikeys)) {
        die("No API keys found in key store. Run: php bin/migrate-keys.php");
    }
    define('API_KEYS', $apikeys);
    return;
}

do {
    $keys_folder = dirname(__FILE__) . '/keys';
    $keys_files  = glob("$keys_folder/*.php");

    if (empty($keys_files)) {
        die("No keys files found in keys folder.");
    }

    $excludes = [
        $keys_folder . "/my_custom_keys.php",
    ];
    $count          = count($keys_files);
    $count_excludes = count($excludes);

    if ($count == $count_excludes) {
        require_once($keys_folder . "/my_custom_keys.php");
    } elseif ($count > $count_excludes) {
        foreach (glob($keys_folder . "/*.php") as $file) {
            if (!in_array($file, $excludes)) {
                require_once($file);
            }
        }
    }

    if (!isset($apikeys) || empty($apikeys)) {
        die("Variable \$apikeys not set. Please check your settings (or more specifically your keys folder).");
    }

    define('API_KEYS', $apikeys);
} while (False);
