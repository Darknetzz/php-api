<?php

$root = dirname(__DIR__);
chdir($root);

require_once $root . '/api_settings.php';

if (!defined('KEY_STORE_DRIVER')) {
    define('KEY_STORE_DRIVER', 'sqlite');
}
if (!defined('KEY_STORE_DSN')) {
    define('KEY_STORE_DSN', sys_get_temp_dir() . '/php-api-test-' . getmypid() . '.db');
}
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

@unlink(KEY_STORE_DSN);
