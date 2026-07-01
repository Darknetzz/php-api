<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_aliases.php                           */
/* ────────────────────────────────────────────────────────────────────────── */

require_once dirname(__FILE__) . '/lib/config_loader.php';

$aliases_folder = dirname(__FILE__) . '/aliases';
$aliases_files = glob($aliases_folder . '/*.php') ?: [];

if ($aliases_files === []) {
    die('No aliases files found in aliases folder.');
}

$endpoint_aliases = [];

$loadFile = function (string $file) use (&$endpoint_aliases): void {
    if (!is_file($file)) {
        return;
    }
    $aliases = null;
    require $file;
    if (!isset($aliases) || !is_array($aliases)) {
        return;
    }
    foreach ($aliases as $canonical => $aliasNames) {
        $canonicalShort = str_starts_with($canonical, 'api_') ? substr($canonical, 4) : $canonical;
        foreach ($aliasNames as $alias) {
            $aliasShort = str_starts_with($alias, 'api_') ? substr($alias, 4) : $alias;
            $endpoint_aliases[$aliasShort] = $canonicalShort;
        }
    }
};

$customFiles = array_filter(
    $aliases_files,
    static fn(string $file): bool => basename($file) !== 'my_custom_aliases.php'
);

if ($customFiles === []) {
    $fallback = $aliases_folder . '/my_custom_aliases.php';
    if (is_file($fallback)) {
        $loadFile($fallback);
    }
} else {
    foreach ($customFiles as $file) {
        $loadFile($file);
    }
}

if (!defined('ENDPOINT_ALIASES')) {
    define('ENDPOINT_ALIASES', $endpoint_aliases);
}
