<?php

$root = dirname(__DIR__);
chdir($root);

// Pin key store to an isolated temp file BEFORE api_settings.php runs.
// Hostname settings use `if (!defined(...))` so this blocks data/api.db.
$testDbPath = sys_get_temp_dir() . '/php-api-test-' . getmypid() . '.db';
$testLogPath = sys_get_temp_dir() . '/php-api-test-' . getmypid() . '.log';
if (!defined('KEY_STORE_DRIVER')) {
    define('KEY_STORE_DRIVER', 'sqlite');
}
if (!defined('KEY_STORE_DSN')) {
    define('KEY_STORE_DSN', $testDbPath);
}
if (!defined('LOG_FILE')) {
    define('LOG_FILE', $testLogPath);
}

require_once $root . '/api_settings.php';

if (!defined('APIKEY_DEFAULT_OPTIONS')) {
    define('APIKEY_DEFAULT_OPTIONS', [
        'allowedEndpoints' => ['*'],
        'disallowedEndpoints' => [],
        'noTimeOut' => false,
        'cooldown' => 1,
        'sleep' => 0,
        'notify' => false,
        'log_write' => false,
    ]);
}

require_once $root . '/lib/bootstrap.php';

// Safe: KEY_STORE_DSN is always the temp path above when tests use this bootstrap.
@unlink($testDbPath);
@unlink($testLogPath);
