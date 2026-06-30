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

require_once dirname(__FILE__) . '/lib/bootstrap.php';

$apikeys = [];
$keyStore = getApiKeyStore();

if ($keyStore instanceof ApiKeyStore) {
    $apikeys = $keyStore->loadAll();
    if (empty($apikeys)) {
        die('No API keys found in key store. Run: php bin/migrate-keys.php');
    }
    define('API_KEYS', $apikeys);
    return;
}

require_once dirname(__FILE__) . '/lib/config_loader.php';

$keys_folder = dirname(__FILE__) . '/keys';
loadPhpConfigDir($keys_folder, ['my_custom_keys.php'], 'my_custom_keys.php');

if (!isset($apikeys) || empty($apikeys)) {
    die('Variable $apikeys not set. Please check your settings (or more specifically your keys folder).');
}

define('API_KEYS', $apikeys);
