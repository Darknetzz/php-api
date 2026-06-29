<?php

/**
 * Load PHP config files from a directory with optional template fallback.
 *
 * @param list<string> $excludeBasenames Basenames to skip (e.g. my_custom_endpoints.php)
 * @param string|null $fallbackBasename Loaded when no other files match (e.g. my_custom_endpoints.php)
 */
function loadPhpConfigDir(string $dir, array $excludeBasenames = [], ?string $fallbackBasename = null): void
{
    $files = glob($dir . '/*.php') ?: [];
    if ($files === []) {
        die("No config files found in $dir");
    }

    $excludePaths = [];
    foreach ($excludeBasenames as $basename) {
        $excludePaths[] = $dir . '/' . $basename;
    }

    $toLoad = [];
    foreach ($files as $file) {
        if (!in_array($file, $excludePaths, true)) {
            $toLoad[] = $file;
        }
    }

    if ($toLoad === []) {
        if ($fallbackBasename !== null) {
            $fallback = $dir . '/' . $fallbackBasename;
            if (is_file($fallback)) {
                require_once $fallback;
                return;
            }
        }
        die("No config files to load in $dir");
    }

    sort($toLoad, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($toLoad as $file) {
        require_once $file;
    }
}
