<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_settings.php                         */
/* ────────────────────────────────────────────────────────────────────────── */
/*
    Load order: hostname/custom settings first, then default_settings.php
    fills any constants not yet defined. See settings/default_settings.php
    for the full list of options.
*/

$settings_folder = dirname(__FILE__) . '/settings';
$settings_files  = glob("$settings_folder/*.php");

if (empty($settings_files)) {
    die("No settings files found in settings folder.");
}

$excludes = [
    $settings_folder . '/default_settings.php',
    $settings_folder . '/custom_api_settings.php',
];

foreach (glob($settings_folder . '/*.php') ?: [] as $file) {
    if (!in_array($file, $excludes, true)) {
        require_once $file;
    }
}

require_once $settings_folder . '/default_settings.php';
