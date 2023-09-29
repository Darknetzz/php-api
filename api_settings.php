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
/*                                   General                                  */
/* ────────────────────────────────────────────────────────────────────────── */
# Will redirect user to somewhere else than index.php if set to true
# if there are no parameters given (endpoint, apikey etc.)
define('ENABLE_CUSTOM_INDEX', false);

# What page should the user be redirected to? Also supports full URLs
# Example: 'https://example.com/wiki/api'
define('CUSTOM_INDEX', 'custom_index.php');


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
        'WARNING' => 10,
        'INFO'    => 20,
        'VERBOSE' => 30,
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
    "allowedEndpoints"    => ["*"],         # allowed endpoints ("*" = all endpoints)
    "disallowedEndpoints" => [],            # forbid this key from an endpoint (will override allowedEndpoints)
    "noTimeOut"           => false,         # allows this key to make unlimited requests with no cooldown
    "cooldown"            => COOLDOWN_TIME, # default cooldown time
    "notify"              => false,         # will notify you if you have set up SMS config
    "log_write"           => true,          # enables write_log function where possible if LOG_ENABLE !== false
]);

/* ────────────────────────────────────────────────────────────────────────── */
/*                            Funny error messages                            */
/* ────────────────────────────────────────────────────────────────────────── */
define("FUNNY_RESPONSES_ENABLE", true);

?>