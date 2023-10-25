<?php
/* ────────────────────────────────────────────────────────────────────────── */
/*                                   index.php                                */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */

require_once('api_includes.php');

if (!$_REQUEST && ENABLE_CUSTOM_INDEX === true) {
    header('Location: '.CUSTOM_INDEX_NOPARAMS);
    die(); # the header should redirect us, but make sure we stop running here.
}

header('Content-type: application/json;'); 

# The endpoint should always be provided in GET
if (!var_assert($_GET['endpoint'])) {
    die(err("No endpoint provided.", 404));
}
$endpoint = "api_".$_GET['endpoint'];

# Apart from that we don't wish to extinguish between request methods (for now), unless unspecified.
if (!empty($_SERVER['REQUEST_METHOD'])) {
    die(err("Invalid request method"));
}
$args = $_REQUEST;

$functionCall = callFunction($endpoint, $args);

echo $functionCall;
?>