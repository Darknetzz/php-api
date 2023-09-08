<?php header('Content-type: application/json;'); 

# require_once(ASSETS_FOLDER.'/includes/sqlcon.php');
require_once('api_settings.php');
require_once('api_base.php');
require_once('api_endpoints.php');
include_once('api_keys.php');
include_once('api_aliases.php');

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

# We want to allow the API key through GET or headers
if (var_assert($_SERVER['HTTP_APIKEY'])) {
    $apikey = $_SERVER['HTTP_APIKEY'];
} elseif (var_assert($args['apikey'])) {
    $apikey = $args['apikey'];
} elseif (var_assert($_GET['apikey'])) {
    $apikey = $_GET['apikey'];
} else {
    die(err("Missing API key", 403));
}

# If we allow the API key in the header, we might as well allow the endpoint, no?

if (!in_array($args['endpoint'], OPEN_ENDPOINTS)) {
    # ["Kristian" => ["key" => "jaqqYvR5bCyiAdg5rAV6DnMpIaxCzgnyoGrba4eITN1s25Pu9o979lnBD5RNhOvX", "allowedEndpoints" => "*"],]
    $valid_apikey = in_md_array("key", $apikey, API_KEYS);
    // if (!isset($_GET['apikey']) || empty($_GET['apikey'])) {
    //     die(err("Missing API key", 403));
    // }
    if (!var_assert($apikey) || !$valid_apikey) {
        die(err("Invalid API key", 403));
    }
    $noTimeOut    = API_KEYS[$valid_apikey]["noTimeOut"];
    $allowedEndpoints = API_KEYS[$valid_apikey]["allowedEndpoints"];
    if (!in_array("*", $allowedEndpoints) && !in_array($args['endpoint'], $allowedEndpoints)) {
        die(err("You do not have access to this endpoint.", 403));
    }
}
# !SECTION

$endpoint = "api_".$args['endpoint'];

$functionCall = callFunction($endpoint, $args, $noTimeOut);

$output = $functionCall;

echo $output;
?>

