<?php
/* ────────────────────────────────────────────────────────────────────────── */
/*                                   index.php                                */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */

header('Content-type: application/json;'); 

require_once('api_includes.php');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $args = $_POST;
} elseif ($_SERVER['REQUEST_METHOD'] == "GET") {
    $args = $_GET;
} else {
    die(err("Unsupported request type.", 500));
}

if (!var_assert($args['endpoint'])) {
    die(err("No endpoint provided.", 404));
}

$endpoint = "api_".$args['endpoint'];

$functionCall = callFunction($endpoint, $args);

$output = $functionCall;

echo $output;
?>