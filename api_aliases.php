<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_aliases.php                        */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/*
    Here you can put your aliases, if you have any.

    The structure must be as follows:
    $aliases = [
          "api_main_function"    => ["api_alias_function_1", "api_alias_function_2"],
          "api_another_function" => ["api_another_function_alias", "api_af_short"],
    ];
    
    These aliases will work for both "internal"/"base" functions and endpoints.
*/



$aliases = [
    "api_example_endpoint"      => ["api_example"],
];

foreach ($aliases as $funcName => $aliases) {
    if (function_exists($funcName)) {
        foreach ($aliases as $alias) {
                // echo "setting function $funcName as $alias<br>";
                $use = "use function $funcName as $alias;";
                eval($use);
        }
    }
}
?>