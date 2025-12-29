<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_settings.php                         */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/*
    # This file should contain the default values for everything in settings -
    # and it should be applied if the constants are not defined, which could cause an error.

    # NOTE: Please do not change this file directly, change the values in
    #       the 'settings' folder instead.
*/

foreach (glob("settings/*.php") as $file) {
    require_once($file);
}

try {

    $defaults = [
        /* ───────────────────────────────────────────────────────────────────── */
        /*                                General                                */
        /* ───────────────────────────────────────────────────────────────────── */
        "ENABLE_CUSTOM_INDEX"          => False,
        "ENABLE_CUSTOM_INDEX_NOPARAMS" => False,
        "CUSTOM_INDEX"                 => "custom_index.php",
        "CUSTOM_INDEX_NOPARAMS"        => "custom_index.php",
        
        /* ───────────────────────────────────────────────────────────────────── */
        /*                           Security Settings                           */
        /* ───────────────────────────────────────────────────────────────────── */
        // TRUST_PROXY: Whether to trust X-Forwarded-For headers for IP detection
        // Only enable this if you're behind a trusted reverse proxy (nginx, Apache, etc.)
        // When false, only REMOTE_ADDR is used (more secure, harder to spoof)
        "TRUST_PROXY"                  => False,
        
        // ALLOW_PRIVATE_IPS: Whether to allow private/reserved IP ranges in X-Forwarded-For
        // Set to true if your API needs to handle requests from internal networks
        // Set to false for stricter validation (blocks RFC1918 and reserved ranges)
        "ALLOW_PRIVATE_IPS"            => True,
        
        // PRODUCTION_MODE: When enabled, reduces information disclosure in error messages
        // Set to true in production environments to avoid exposing internal details
        "PRODUCTION_MODE"              => False,
        
        // CORS_ALLOW_ORIGIN: Configure Cross-Origin Resource Sharing
        // Set to '*' to allow all origins (less secure but more permissive)
        // Set to specific domain like 'https://example.com' for restricted access
        // Default is '*' for backward compatibility
        "CORS_ALLOW_ORIGIN"            => "*",

        /* ───────────────────────────────────────────────────────────────────── */
        /*                           HTTP Status codes                           */
        /* ───────────────────────────────────────────────────────────────────── */
        "HTTP_STATUS_CODES"            => [
            "ERROR" => 500,
            "OK"    => 200,
        ],

        /* ────────────────────────────────────────────────────────────────────────── */
        /*                          Response format defaults                          */
        /* ────────────────────────────────────────────────────────────────────────── */
        "DEFAULT_FILTER"       => null,
        "DEFAULT_JSON_COMPACT" => False,
        "VERBOSE_API"          => False,
        "NOTIFY_API"           => False,
        "NOTIFY_NUMBER"        => "12345678",
        "LOG_ENABLE"           => True,
        "LOG_FILE"             => 'api.log',
        "LOG_LEVEL"            => 'info',
        "LOG_LEVELS"           => [
            'WARNING' => 10,
            'INFO'    => 20,
            'VERBOSE' => 30,
        ],
        "GLOBAL_PARAMS"        => [
                "apikey",
                "endpoint",
                "filter",
                "filterdata",
                "clean",
                "compact",
                "verbose",
        ],

        /* ────────────────────────────────────────────────────────────────────────── */
        /*       VALID_FILTERS: Specifies available filters (for all endpoints)       */
        /* ────────────────────────────────────────────────────────────────────────── */
        "VALID_FILTERS"        => [
            "httpCode",
            "data",
            "status",
        ],

        /* ────────────────────────────────────────────────────────────────────────── */
        /*         WHITELIST_MODE:                                                    */
        /*           [TRUE] / Whitelist mode (default): will protect all endpoints    */
        /*            except the ones defined in OPEN_ENDPOINTS                       */
        /* ────────────────────────────────────────────────────────────────────────── */
        /*           [FALSE] / Blacklist mode (not recommended):                      */
        /*           will only consider the PROTECTED_ENDPOINT list,                  */
        /*           everything else will be open                                     */
        /* ────────────────────────────────────────────────────────────────────────── */
        "WHITELIST_MODE" => True,

        /* ────────────────────────────────────────────────────────────────────────── */
        /* OPEN_ENDPOINTS: Specifies endpoints that do not require authorization.     */
        /*       Be careful with this as you are potentially exposing yourself.       */
        /* ────────────────────────────────────────────────────────────────────────── */
        "OPEN_ENDPOINTS" => [
            "api_some_open_endpoint",
            "api_another_open_endpoint",
        ],

        /* ────────────────────────────────────────────────────────────────────────── */
        /* PROTECTED_ENDPOINTS: Specifies endpoints that requires authorization,      */
        /* This only applies if <SOME OTHER CONSTANT> is set to blacklist mode.       */
        /* ────────────────────────────────────────────────────────────────────────── */
        "PROTECTED_ENDPOINTS"  => [
            "api_some_open_endpoint",
            "api_another_open_endpoint",
        ],

        /* ────────────────────────────────────────────────────────────────────────── */
        /*                                 Time stuff                                 */
        /* ────────────────────────────────────────────────────────────────────────── */
        "NOW"                    => round(microtime(True)),
        "LAST_CALLED_JSON"       => "endpoints_lastcalled.json",
        "COOLDOWN_TIME"          => 1,
        "SLEEP_TIME"             => 2,


        /* ────────────────────────────────────────────────────────────────────────── */
        /*                           API Key default options                          */
        /* ────────────────────────────────────────────────────────────────────────── */
        "APIKEY_DEFAULT_OPTIONS" => [
            "allowedEndpoints"    => ["*"],         # allowed endpoints ("*" = all endpoints)
            "disallowedEndpoints" => [],            # forbid this key from an endpoint (will override allowedEndpoints)
            "noTimeOut"           => False,         # allows this key to make unlimited requests with no cooldown
            "cooldown"            => 1,             # default cooldown time
            "sleep"               => 2,             # default time to sleep before response
            "notify"              => False,         # will notify you if you have set up SMS config
            "log_write"           => True,          # enables write_log function where possible if LOG_ENABLE !== False
        ],

        /* ────────────────────────────────────────────────────────────────────────── */
        /*                            Funny error messages                            */
        /* ────────────────────────────────────────────────────────────────────────── */
        "FUNNY_RESPONSES_ENABLE" => True,
    ];

    foreach ($defaults as $const => $val) {
        if (!defined($const)) {
            define($const, $val);
        }
    }

} catch (Exception $e) {
    die("Error loading default settings: ".$e->getMessage());
}
?>