<?php
/* ────────────────────────────────────────────────────────────────────────── */
/*                                   index.php                                */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */

function shutdown_find_exit()
{
    var_dump($GLOBALS['dbg_stack']);
}
register_shutdown_function('shutdown_find_exit');
function write_dbg_stack()
{
    $GLOBALS['dbg_stack'] = debug_backtrace();
}
register_tick_function('write_dbg_stack');
declare(ticks=1);

header('Content-type: application/json;');
header('Access-Control-Allow-Origin: *;');

/* ───────────────────────────────────────────────────────────────────── */
/*                         Require settings file                         */
/* ───────────────────────────────────────────────────────────────────── */
#       this needs to be done here because we allow custom a index
# Check for custom settings file first, then include api_settings.php regardless
# as it will set defaults if it's not defined by custom_settings.
$custom_settings    = 'custom_api_settings.php';
$default_settings   = 'api_settings.php';
if (file_exists($custom_settings)) {
    require_once($custom_settings);
}
if (!file_exists($default_settings)) {
    die("Required default settings file '$default_settings' not found.");
}
require_once($default_settings);
/* ───────────────────────────────────────────────────────────────────── */

if (defined('ENABLE_CUSTOM_INDEX_NOPARAMS') 
    && ENABLE_CUSTOM_INDEX_NOPARAMS === true
    && defined('CUSTOM_INDEX_NOPARAMS')
    && empty($_REQUEST) 
    && basename(__FILE__) !== basename(CUSTOM_INDEX_NOPARAMS)) {
    header('Location: '.CUSTOM_INDEX_NOPARAMS);
    die(); # the header should redirect us, but make sure we stop running here.
}

if (defined('ENABLE_CUSTOM_INDEX')
    && ENABLE_CUSTOM_INDEX === true
    && defined('CUSTOM_INDEX')
    && !empty($_REQUEST)
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