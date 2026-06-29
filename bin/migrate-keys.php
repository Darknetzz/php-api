#!/usr/bin/env php
<?php

/**
 * Migrate API keys from keys/custom_api_keys.php into SQLite/Turso store.
 *
 * Usage:
 *   php bin/migrate-keys.php [--rotate] [--dry-run]
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
chdir($root);

if (!defined('KEY_STORE_DRIVER')) {
    define('KEY_STORE_DRIVER', getenv('KEY_STORE_DRIVER') ?: 'sqlite');
}
if (!defined('KEY_STORE_DSN')) {
    define('KEY_STORE_DSN', getenv('KEY_STORE_DSN') ?: $root . '/data/api.db');
}

require_once $root . '/api_settings.php';
require_once $root . '/lib/bootstrap.php';

$rotate = in_array('--rotate', $argv, true);
$dryRun = in_array('--dry-run', $argv, true);

if (KEY_STORE_DRIVER === 'php') {
    fwrite(STDERR, "Set KEY_STORE_DRIVER to sqlite or turso before migrating.\n");
    exit(1);
}

$store = ApiKeyStore::createFromConfig();
if (!$store instanceof ApiKeyStore) {
    fwrite(STDERR, "Failed to initialize key store.\n");
    exit(1);
}

$keysFile = $root . '/keys/custom_api_keys.php';
if (!is_file($keysFile)) {
    fwrite(STDERR, "No keys file found at keys/custom_api_keys.php\n");
    exit(1);
}

$apikeys = [];

function addAPIKey(string $name, string $key, array $options = []): void
{
    global $apikeys;
    $apikeys[$name] = [
        'key' => $key,
        'options' => ApiKeyStore::mergeDefaultOptions($options),
    ];
}

require $keysFile;

if (empty($apikeys)) {
    fwrite(STDERR, "No keys loaded from custom_api_keys.php\n");
    exit(1);
}

$migrated = 0;
$skipped  = 0;
$newKeys  = [];

foreach ($apikeys as $name => $entry) {
    if ($store->exists($name)) {
        echo "SKIP (exists): $name\n";
        $skipped++;
        continue;
    }

    $plaintext = $rotate ? ApiKeyStore::generateKey() : ($entry['key'] ?? '');
    if ($plaintext === '') {
        echo "SKIP (empty key): $name\n";
        $skipped++;
        continue;
    }

    $options = $entry['options'] ?? [];

    if ($dryRun) {
        echo "DRY-RUN would migrate: $name\n";
        $migrated++;
        if ($rotate) {
            $newKeys[$name] = $plaintext;
        }
        continue;
    }

    $store->create($name, $plaintext, $options);
    echo "MIGRATED: $name\n";
    $migrated++;

    if ($rotate) {
        $newKeys[$name] = $plaintext;
    }
}

echo "\nDone. Migrated: $migrated, Skipped: $skipped\n";

if ($rotate && !empty($newKeys)) {
    echo "\n--- NEW KEYS (save these; plaintext is not stored in DB) ---\n";
    foreach ($newKeys as $name => $key) {
        echo "$name: $key\n";
    }
}
