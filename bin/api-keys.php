#!/usr/bin/env php
<?php

/**
 * CLI for API key management (SQLite/Turso store).
 *
 * Usage:
 *   php bin/api-keys.php list
 *   php bin/api-keys.php create <name> [--endpoint=name] [--no-timeout]
 *   php bin/api-keys.php disable <name>
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
chdir($root);

require_once $root . '/api_settings.php';
require_once $root . '/lib/bootstrap.php';

if (!defined('KEY_STORE_DRIVER') || KEY_STORE_DRIVER === 'php') {
    fwrite(STDERR, "KEY_STORE_DRIVER must be sqlite or turso.\n");
    exit(1);
}

$store = ApiKeyStore::createFromConfig();
$cmd = $argv[1] ?? 'help';

switch ($cmd) {
    case 'list':
        foreach ($store->loadAll() as $name => $entry) {
            $allowed = implode(',', $entry['options']['allowedEndpoints'] ?? ['*']);
            echo "$name (allowed: $allowed)\n";
        }
        break;

    case 'create':
        $name = $argv[2] ?? '';
        if ($name === '') {
            fwrite(STDERR, "Usage: php bin/api-keys.php create <name>\n");
            exit(1);
        }
        if ($store->exists($name)) {
            fwrite(STDERR, "Key name already exists: $name\n");
            exit(1);
        }
        $options = [];
        foreach (array_slice($argv, 3) as $arg) {
            if (str_starts_with($arg, '--endpoint=')) {
                $options['allowedEndpoints'] = [substr($arg, 11)];
            }
            if ($arg === '--no-timeout') {
                $options['noTimeOut'] = true;
            }
        }
        $key = ApiKeyStore::generateKey();
        $store->create($name, $key, $options);
        echo "Created key '$name':\n$key\n";
        break;

    case 'disable':
        $name = $argv[2] ?? '';
        if ($name === '') {
            fwrite(STDERR, "Usage: php bin/api-keys.php disable <name>\n");
            exit(1);
        }
        $store->disable($name);
        echo "Disabled: $name\n";
        break;

    default:
        echo "Commands: list, create <name>, disable <name>\n";
        break;
}
