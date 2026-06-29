#!/usr/bin/env php
<?php

/**
 * CLI for API key management (SQLite/Turso store).
 *
 * Usage:
 *   php bin/api-keys.php list
 *   php bin/api-keys.php show <name>
 *   php bin/api-keys.php create <name> [--endpoint=name] [--no-timeout] [--cooldown=N]
 *   php bin/api-keys.php update <name> [--endpoint=name] [--no-timeout] [--cooldown=N] [--disallowed=name]
 *   php bin/api-keys.php enable <name>
 *   php bin/api-keys.php disable <name>
 *   php bin/api-keys.php rotate <name>
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

/** @return array<string, mixed> */
function apiKeysCliParseOptions(array $argv): array
{
    $options = [];
    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--endpoint=')) {
            $options['allowedEndpoints'] = [substr($arg, 11)];
        }
        if (str_starts_with($arg, '--disallowed=')) {
            $options['disallowedEndpoints'] = [substr($arg, 13)];
        }
        if ($arg === '--no-timeout') {
            $options['noTimeOut'] = true;
        }
        if (str_starts_with($arg, '--cooldown=')) {
            $options['cooldown'] = (int) substr($arg, 11);
        }
    }

    return $options;
}

$store = ApiKeyStore::createFromConfig();
$cmd = $argv[1] ?? 'help';

switch ($cmd) {
    case 'list':
        foreach ($store->listDetailed() as $row) {
            $status = $row['enabled'] ? 'enabled' : 'disabled';
            $allowed = implode(',', $row['options']['allowedEndpoints'] ?? ['*']);
            echo "{$row['name']} ($status, allowed: $allowed)\n";
        }
        break;

    case 'show':
        $name = $argv[2] ?? '';
        if ($name === '') {
            fwrite(STDERR, "Usage: php bin/api-keys.php show <name>\n");
            exit(1);
        }
        $row = $store->getByName($name);
        if ($row === null) {
            fwrite(STDERR, "Key not found: $name\n");
            exit(1);
        }
        echo json_encode($row, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
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
        $options = apiKeysCliParseOptions(array_slice($argv, 3));
        $key = ApiKeyStore::generateKey();
        $store->create($name, $key, $options);
        echo "Created key '$name':\n$key\n";
        break;

    case 'update':
        $name = $argv[2] ?? '';
        if ($name === '') {
            fwrite(STDERR, "Usage: php bin/api-keys.php update <name>\n");
            exit(1);
        }
        if (!$store->exists($name)) {
            fwrite(STDERR, "Key not found: $name\n");
            exit(1);
        }
        $patch = apiKeysCliParseOptions(array_slice($argv, 3));
        if ($patch === []) {
            fwrite(STDERR, "No options given. Use --endpoint=, --no-timeout, --cooldown=, or --disallowed=\n");
            exit(1);
        }
        $store->updateOptions($name, $patch);
        echo "Updated: $name\n";
        break;

    case 'enable':
        $name = $argv[2] ?? '';
        if ($name === '') {
            fwrite(STDERR, "Usage: php bin/api-keys.php enable <name>\n");
            exit(1);
        }
        try {
            $store->enable($name);
            echo "Enabled: $name\n";
        } catch (InvalidArgumentException $e) {
            fwrite(STDERR, $e->getMessage() . "\n");
            exit(1);
        }
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

    case 'rotate':
        $name = $argv[2] ?? '';
        if ($name === '') {
            fwrite(STDERR, "Usage: php bin/api-keys.php rotate <name>\n");
            exit(1);
        }
        try {
            $key = $store->rotate($name);
            echo "Rotated '$name'. New secret (save now):\n$key\n";
        } catch (InvalidArgumentException $e) {
            fwrite(STDERR, $e->getMessage() . "\n");
            exit(1);
        }
        break;

    default:
        echo "Commands: list, show <name>, create <name>, update <name>, enable <name>, disable <name>, rotate <name>\n";
        break;
}
