<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_settings.php                         */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/*
    Here you can make customizations to your API.
    It contains some default values that could be tweaked.
*/


/* ────────────────────────────────────────────────────────────────────────── */
/*                              HTTP Status codes                             */
/* ────────────────────────────────────────────────────────────────────────── */
define("HTTP_STATUS_CODES", [
    "ERROR" => 500,
    "OK" => 200,
]);

/* ────────────────────────────────────────────────────────────────────────── */
/*                          Response format defaults                          */
/* ────────────────────────────────────────────────────────────────────────── */
define("DEFAULT_FILTER"      , null);
define("DEFAULT_JSON_COMPACT", false);
define("VERBOSE_API"         , false); # appends additional information to json response (soon)
define("NOTIFY_API"          , false); # notifiy API usage on SMS (unless API key has notify == false)
define("NOTIFY_NUMBER"       , "12345678");
define("LOG_ENABLE"          , true);       # whether or not to enable logging API requests to a file
define("LOG_FILE"            , 'api.log');  # file to write to (if LOG_ENABLE !== false)
define("LOG_LEVEL"           , 'info');     # see below
define("LOG_LEVELS"          ,  
    [
        'warning' => 10,
        'info'    => 20,
        'verbose' => 30,
    ]);

define("GLOBAL_PARAMS",
        [
            "apikey",
            "endpoint",
            "filter",
            "filterdata",
            "clean",
            "compact",
            "verbose",
        ]
);

/* ────────────────────────────────────────────────────────────────────────── */
/*       VALID_FILTERS: Specifies available filters (for all endpoints)       */
/* ────────────────────────────────────────────────────────────────────────── */
define("VALID_FILTERS",
    [
        "httpCode",
        "data",
        "status",
    ]
);

/* ────────────────────────────────────────────────────────────────────────── */
/* OPEN_ENDPOINTS: Specifies endpoints that do not require authorization.     */
/*       Be careful with this as you are potentially exposing yourself.       */
/* ────────────────────────────────────────────────────────────────────────── */
define("OPEN_ENDPOINTS",
    [
        "api_some_open_endpoint",
        "api_another_open_endpoint",
    ]
);

/* ────────────────────────────────────────────────────────────────────────── */
/*                                 Time stuff                                 */
/* ────────────────────────────────────────────────────────────────────────── */
$now                     = round(microtime(true));
define("NOW"             , $now);
define("LAST_CALLED_JSON", "endpoints_lastcalled.json");
define("COOLDOWN_TIME"   , 5);


/* ────────────────────────────────────────────────────────────────────────── */
/*                           API Key default options                          */
/* ────────────────────────────────────────────────────────────────────────── */
define("APIKEY_DEFAULT_OPTIONS", [
    "allowedEndpoints"    => ["*"],
    "disallowedEndpoints" => [],
    "noTimeOut"           => false,
    "cooldown"            => COOLDOWN_TIME,
    "notify"              => false,
]);

/* ────────────────────────────────────────────────────────────────────────── */
/*                            Funny error messages                            */
/* ────────────────────────────────────────────────────────────────────────── */
define("FUNNY_RESPONSES_ENABLE", true);

?>