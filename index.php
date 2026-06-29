<?php
/* ────────────────────────────────────────────────────────────────────────── */
/*                                   index.php                                */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */

header('Content-type: application/json;');
// CORS: set in settings via CORS_ALLOW_ORIGIN (e.g. '*' or 'https://example.com')
if (defined('CORS_ALLOW_ORIGIN')) {
    header('Access-Control-Allow-Origin: '.CORS_ALLOW_ORIGIN);
} else {
    header('Access-Control-Allow-Origin: *');
}

/* ───────────────────────────────────────────────────────────────────── */
/*                         Require settings file                         */
/* ───────────────────────────────────────────────────────────────────── */
# this needs to be done here because we allow custom a index
# Check for custom settings file first, then include api_settings.php regardless
# as it will set defaults if it's not defined by custom_settings.
require_once('api_settings.php');
/* ───────────────────────────────────────────────────────────────────── */

if (defined('ENABLE_CUSTOM_INDEX_NOPARAMS') 
    && ENABLE_CUSTOM_INDEX_NOPARAMS === true
    && defined('CUSTOM_INDEX_NOPARAMS')
    && empty($_REQUEST) 
    && basename(__FILE__) !== basename(CUSTOM_INDEX_NOPARAMS)) {
    // Security: Validate redirect URL to prevent open redirect
    // Only allow simple filenames without directory traversal
    $redirect = CUSTOM_INDEX_NOPARAMS;
    $basename = basename($redirect);
    if ($basename !== $redirect || !preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $basename)) {
        die(err("Invalid custom index configuration", 500));
    }
    header('Location: '.htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8'));
    // die(); # the header should redirect us, but make sure we stop running here.
}

if (defined('ENABLE_CUSTOM_INDEX')
    && ENABLE_CUSTOM_INDEX === true
    && defined('CUSTOM_INDEX')
    && !empty($_REQUEST)
    && basename(__FILE__) !== basename(CUSTOM_INDEX)) {
    // Security: Validate redirect URL to prevent open redirect
    // Only allow simple filenames without directory traversal
    $redirect = CUSTOM_INDEX;
    $basename = basename($redirect);
    if ($basename !== $redirect || !preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $basename)) {
        die(err("Invalid custom index configuration", 500));
    }
    header('Location: '.htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8')."?".http_build_query($_REQUEST));
    // die(); # the header should redirect us, but make sure we stop running here.
}

require_once('api_base.php');
require_once('api_endpoints.php');
require_once('api_keys.php');
require_once('api_aliases.php');

# The endpoint should always be provided in GET
if (!var_assert($_REQUEST['endpoint'])) {
    if (defined('ENABLE_API_GUI') && ENABLE_API_GUI === true && file_exists("api_gui.php")) {
        header('Location: api_gui.php');
        die();
    }
    die(err("No endpoint provided.", 404));
}

// Security: Validate endpoint name to prevent code injection
$endpoint_input = resolveEndpointName($_REQUEST['endpoint']);
if (!preg_match('/^[a-zA-Z0-9_]+$/', $endpoint_input)) {
    die(err("Invalid endpoint name. Only alphanumeric characters and underscores are allowed.", 400));
}

$endpoint = "api_".$endpoint_input;
if (!function_exists($endpoint)) {
    $fallback = "api_" . preg_replace('/[^a-zA-Z0-9_]/', '', (string) $_REQUEST['endpoint']);
    if (function_exists($fallback)) {
        $endpoint = $fallback;
    }
}

# Apart from that we don't wish to extinguish between request methods (for now), unless unspecified.
if (empty($_SERVER['REQUEST_METHOD'])) {
    die(err("Invalid request method"));
}
$args = mergeAuthHeaders($_REQUEST);

$functionCall = callFunction($endpoint, $args);

echo $functionCall;
?>