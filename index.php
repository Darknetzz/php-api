<?php
/* ────────────────────────────────────────────────────────────────────────── */
/*                                   index.php                                */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */


# Require settings file -
# this needs to be done here because we allow custom a index
$settings = 'api_settings.php';
if (file_exists('custom_'.$settings)) {
    require_once('custom_'.$settings);
} else {
    require_once($settings);
}

if (defined('ENABLE_CUSTOM_INDEX_NOPARAMS') &&
    defined('CUSTOM_INDEX_NOPARAMS')
    && empty($_REQUEST) 
    && basename(__FILE__) !== basename(CUSTOM_INDEX_NOPARAMS)) {
    header('Location: '.CUSTOM_INDEX_NOPARAMS);
    die(); # the header should redirect us, but make sure we stop running here.
}

if ($_REQUEST 
    && ENABLE_CUSTOM_INDEX === true 
    && basename(__FILE__) !== basename(CUSTOM_INDEX)) {
    header('Location: '.CUSTOM_INDEX."?".http_build_query($_REQUEST));
    die(); # the header should redirect us, but make sure we stop running here.
}

require_once('api_includes.php');

header('Content-type: application/json;'); 

# The endpoint should always be provided in GET
# EDIT 2023-11-06: does it really?
if (!var_assert($_REQUEST['endpoint'])) {
    die(err("No endpoint provided.", 404));
}
$endpoint = "api_".$_REQUEST['endpoint'];

# Apart from that we don't wish to extinguish between request methods (for now), unless unspecified.
if (empty($_SERVER['REQUEST_METHOD'])) {
    die(err("Invalid request method"));
}
$args = $_REQUEST;

$functionCall = callFunction($endpoint, $args);

echo $functionCall;
?>