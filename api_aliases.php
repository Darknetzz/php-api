<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_aliases.php                           */
/* ────────────────────────────────────────────────────────────────────────── */

do {
    $aliases_folder = dirname(__FILE__) . '/aliases';
    $aliases_files  = glob("$aliases_folder/*.php");

    if (empty($aliases_files)) {
        die("No aliases files found in aliases folder.");
    }

    $excludes = [
        $aliases_folder . "/my_custom_aliases.php",
    ];
    $count          = count($aliases_files);
    $count_excludes = count($excludes);

    $endpoint_aliases = [];

    $loadFile = function (string $file) use (&$endpoint_aliases) {
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

    if ($count == $count_excludes) {
        $loadFile($aliases_folder . "/my_custom_aliases.php");
    } elseif ($count > $count_excludes) {
        foreach (glob($aliases_folder . "/*.php") as $file) {
            if (!in_array($file, $excludes)) {
                $loadFile($file);
            }
        }
    } else {
        die("Something went wrong while loading aliases files.");
    }

    if (!defined('ENDPOINT_ALIASES')) {
        define('ENDPOINT_ALIASES', $endpoint_aliases);
    }
} while (False);
