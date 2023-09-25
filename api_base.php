<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_base.php                             */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/* 
    This file contains the API base functions - in other words essential functions
    for the API to work. You can of course tweak it however you want, but most of the config
    should be done in api_settings.php and not here, unless you know what you're doing.
*/



/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function err */
/* ────────────────────────────────────────────────────────────────────────── */
function err(string $text, int $statusCode = 500, bool $fatal = true) {
    http_response_code($statusCode);
    return json_encode(
        [
            "httpCode" => $statusCode,
            "status" => "ERROR",
            "data" => $text,
        ]
    );
    die(); # just gotta make sure we are not continuing
}


/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function var_assert */
/* ────────────────────────────────────────────────────────────────────────── */
function var_assert(mixed &$var, mixed $assertVal = false, bool $lazy = false) : bool {
    if (!isset($var) || empty($var)) {
        return false;
    }

    if ($assertVal != false || func_num_args() > 1) {

        if ($lazy != false) {
            return $var == $assertVal;
        }

        return $var === $assertVal;
    }
    
    return true;
}


/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function funnyResponse */
/* ────────────────────────────────────────────────────────────────────────── */
function funnyResponse(string $type, array $vars = []) : string {
    /* ──────────────────────────── Mandatory checks ──────────────────────────── */
    if (!isset($vars["endpoint"]) || empty($vars["endpoint"])) {
        return "It's hard to generate a funny response to an endpoint that doesn't exist!";
    }

    if (!endpointExists($vars["endpoint"])) {
        return "I'm not sure what endpoint you are trying to connect to... What is $vars[endpoint]?";
    }

    $func = $vars["endpoint"];

    /* ────────────────────────────────── Vars ────────────────────────────────── */
    $validVars = [
        "endpoint",
        "secondsToWait",
        "requiredParams",
        "requiredParamCount",
        "specifiedParams",
        "allParamCount",
        "paramsCleanCount",
        "secondsSinceLastCalled",
    ];

    if (!is_array($vars)) {
        return "No vars passed to funnyResponse(), what am I supposed to do then?";
    }

    foreach ($validVars as $validVar) {
        $$validVar = "";
    }
    foreach ($vars as $varVar => $varVal) {
        if (!in_array($varVar, $validVars)) {
            return "You have specified an invalid variable: $varVar";
        }
        $$varVar = $varVal;
    }

    /* ────────────────────────────────────────────────────────────────────────── */


    /* ────────────────────────────────── Types ───────────────────────────────── */
    $validTypes = [
        "COOLDOWN" => [
            "default" => "The endpoint '$func' was called a mere $secondsSinceLastCalled seconds ago! Please wait another $secondsToWait.",
            "funny" => [
                "Wooow, not so fast cowboy! You still need to wait $secondsToWait.",
                "I understand that you're in a hurry, but could you please just wait $secondsToWait before trying again?",
                "Waaaaaiiiiiittttt! Please try again in $secondsToWait!",
            ],
        ],
        "WRONG_PARAM_COUNT" => [
            "default" => "Wrong amount of parameters given. Endpoint '$func' has $allParamCount available parameters ($requiredParamCount required). you provided $paramsCleanCount.",
            "funny" => [
                "Wait a minute, you specified $paramsCleanCount, but this endpoint requires $requiredParamCount of them...",
                "Alright now you are confusing me... I need $requiredParamCount parameters for this function to work, but for some reason you gave me only $paramsCleanCount.",
            ],
        ],
        "ENDPOINT_FALSY" => [
            "default" => "The endpoint '$func' returned an empty/false response.",
            "funny" => [
                "This endpoint isn't being a sport today, and returned a negative response.",
                "Endpoint says no, sorry pal...",
                "I think the endpoint is having a bad day (it sure looks negative to me).",
            ],
        ],
    ];

    if (array_key_exists($type, $validTypes) === false) {
        return "A funny response would be generated, if it had an existing type... But '$type' isn't what I'm looking for!";
    }


    if (FUNNY_RESPONSES_ENABLE !== true) {
        return (!empty($validTypes[$type]["default"]) ? $validTypes[$type]["default"] : "You don't want funny responses, but you cba to set a default response? Okay then...");
    }

    if (!is_array($validTypes[$type]["funny"]) || count($validTypes[$type]["funny"]) < 1) {
        return $validTypes[$type]["default"];
    }

    $count = count($validTypes[$type]["funny"]);

    if ($count > 0) {
        $rand = mt_rand(0, $count-1);
        $response = (isset($validTypes[$type]["funny"][$rand]) ? $validTypes[$type]["funny"][$rand] : $validTypes[$type]["default"]);
    } else {
        $response = $validTypes[$type]["default"];
    }

    return $response;
    
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function api_response */
/* ────────────────────────────────────────────────────────────────────────── */
function api_response(string $status, mixed $data) : string {
    
    global $_GET;
    $params = $_GET;

    $pretty_print = 0;
    if (var_assert(($options['compact']), true)) {
        $pretty_print = JSON_PRETTY_PRINT;
    }

    if (!array_key_exists($status, HTTP_STATUS_CODES)) {
        return err("Invalid status");
    }

    $httpCode = HTTP_STATUS_CODES[$status];
    $verboseInfo = "";

    if (var_assert($data["verboseInfo"])) {
        $verboseInfo = $data["verboseInfo"];
        unset($data["verboseInfo"]);
    }

    $return = ["httpCode" => $httpCode, "status" => $status, "data" => $data];



    # filter (httpCode / status / data)
    if (var_assert($params['filter'])) {

        $filter = $params['filter'];
        if (!var_assert($return[$filter])) {
            return err("The filter $filter isn't valid for this endpoint. Valid options are: ".implode(", ", VALID_FILTERS));
        }
        
        $return = [$filter => $return[$filter]];
    }
    # /filter



    # filterdata (endpoint output filtering)
    if (var_assert($params['filterdata'])) {
        $filterdata = strstr($params['filterdata'], " ", "");
        $filterdata = explode(",", $params['filterdata']);

        $allFilters = [];
        foreach ($filterdata as $thisfilter) {
            if (!var_assert($return['data']['response'][$thisfilter])) {
                // $validOptions = (count($verboseInfo['allParamNames']) > 0 ? implode(",", $verboseInfo['allParamNames']) : "none");
                return err("The datafilter $thisfilter isn't valid for this endpoint.");
            }

            array_push($allFilters, $return['data']['response'][$thisfilter]);
        }

        $return = $allFilters;
    }
    # /filter



    # verboseinfo
    if (var_assert($params['verbose'], 'true')) {
        if (!var_assert($verboseInfo)) {
            $return['data']['response']['verboseInfo'] = "The verbose flag was present, but the content is empty.";
        } else {
            $return['data']['response']['verboseInfo'] = $verboseInfo;
        }
    }
    # /verboseinfo


    # clean
    if (var_assert($params['clean'], 'true')) {
        if (is_array($data['response'])) {
            return err("The 'clean' option for this endpoint is disabled because it returns an array.");
        }
        return reset($data['response']);
    }
    # /clean


    return json_encode($return, $pretty_print);
}


/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function callFunction */
/* ────────────────────────────────────────────────────────────────────────── */
function callFunction(string $func, array $params = []) {

    try {

        $args = $params;
        $apikey = (var_assert($params["apikey"]) ? $params["apikey"] : "all");
        $apikey_options = (var_assert($apikey_options) ? $apikey_options : APIKEY_DEFAULT_OPTIONS);
        $endpoint = (var_assert($params["endpoint"]) ? $params["endpoint"] : null);

        if (!var_assert($endpoint)) {
            die(err("No endpoint was provided"));
        }

        # This is NOT an open endpoint
        if (!in_array($args['endpoint'], OPEN_ENDPOINTS)) {

            $valid_apikey = apikey_validate($apikey);

            if (!var_assert($apikey) || !$valid_apikey) {
                die(err("Invalid API key", 403));
            }

            $apikey_options = (isset(API_KEYS[$valid_apikey]["options"]) ? API_KEYS[$valid_apikey]["options"] : APIKEY_DEFAULT_OPTIONS);

            if (in_array($args['endpoint'], $apikey_options["disallowedEndpoints"])) {
                die(err("You are blacklisted/disallowed from using this endpoint."));
            }
        
            if (!in_array("*", $apikey_options["allowedEndpoints"]) && !in_array($args['endpoint'], $apikey_options["allowedEndpoints"])) {
                die(err("You do not have access to this endpoint.", 403));
            }

        }

        if (!function_exists($func)) {
            return err("Invalid endpoint '$func'");
        }

        foreach (APIKEY_DEFAULT_OPTIONS as $optVar => $optVal) {
            if (!array_key_exists($optVar, $apikey_options)) {
                $apikey_options[$optVar] = $optVal;
            }
        }

        $paramsClean = [];
        foreach ($params as $paramName => $paramValue) {
                if (!in_array($paramName, GLOBAL_PARAMS)) {
                    $paramsClean[$paramName] = $paramValue;
                }
        }

            $functionObject         = new ReflectionFunction($func);
            $allParamNames          = $functionObject-> getParameters();
            $allParamCount          = $functionObject-> getNumberOfParameters();
            $requiredParamCount     = $functionObject-> getNumberOfRequiredParameters();
            $providedParamCount     = count($params);
            $paramsCleanCount       = count($paramsClean);
            $secondsSinceLastCalled = secondsSinceLastCalled($func, $apikey);
            

            if (!$secondsSinceLastCalled) {
                die(err("Function secondsSinceLastCalled() failed"));
            }

            $verboseInfo = [
                "allParamNames"         => $allParamNames,
                "allParamCount"         => $allParamCount,
                "requiredParamCount"    => $requiredParamCount,
                "providedParamCount"    => $providedParamCount,
                "paramsCleanCount"      => $paramsCleanCount,
            ];

        # Error: Wrong amount of parameters given
        if ($paramsCleanCount < $requiredParamCount) {
            return err(funnyResponse(
                "WRONG_PARAM_COUNT", [
                    "endpoint"              => $func,
                    "allParamCount"         => $allParamCount,
                    "paramsCleanCount"      => $paramsCleanCount,
                    "requiredParamCount"    => $requiredParamCount,
                ]));
        }

        # Error: Too quick!
        if ($secondsSinceLastCalled < COOLDOWN_TIME && !$apikey_options["noTimeOut"]) {
            return err(funnyResponse(
                "COOLDOWN", [
                    "endpoint" => $func,
                    "secondsSinceLastCalled" => $secondsSinceLastCalled,
                    "secondsToWait" => (COOLDOWN_TIME - $secondsSinceLastCalled)
                ]));
            // return err("The endpoint '$func' was called a mere ".$secondsSinceLastCalled." seconds ago! Please wait another ".(COOLDOWN_TIME - $secondsSinceLastCalled)." seconds.");
        }

        $functionCall = $functionObject->invoke(...$paramsClean);
        updateLastCalled($func, $apikey);

        if (!$functionCall) {
            return err("The endpoint '$func' returned an empty/false response.");
        }

        if (NOTIFY_API == true) {
            if (API_KEYS[$valid_apikey]["options"]["notify"] != false) {
                api_sms(NOTIFY_NUMBER, "API Called by $valid_apikey: $args[endpoint]");
            }
            if (empty($valid_apikey)) {
                api_sms(NOTIFY_NUMBER, "API Called by unknown user: $args[endpoint]");
            }
        }

        return api_response(status: "OK", data: ["response" => $functionCall, "verboseInfo" => $verboseInfo]);
    } catch (Throwable $e) {
        return err("Exception encountered while calling endpoint $func. $e");
    }
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function secondsSinceLastCalled */
/* ────────────────────────────────────────────────────────────────────────── */
function secondsSinceLastCalled($function_name, $apikey = "all") {
    try {
            $json_contents = file_get_contents(LAST_CALLED_JSON);
            $lf = json_decode($json_contents, true);
            
            $apikey_name = $apikey;
            if ($apikey != "all") {
                $apikey_name = apikey_validate($apikey);
            }

            if (!var_assert($lf[$function_name])) {
                $lastcalled = (NOW - COOLDOWN_TIME);
            } elseif (!var_assert($lf[$function_name][$apikey_name])) {
                $lastcalled = (NOW - COOLDOWN_TIME);
            } else {
                $lastcalled = $lf[$function_name][$apikey_name];
            }

            return (NOW - $lastcalled);

    } catch (Exception $e) {
        return false;
    }
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function updateLastCalled */
/* ────────────────────────────────────────────────────────────────────────── */
function updateLastCalled($function_name, $apikey = "all") {
    if (!file_exists(LAST_CALLED_JSON) || empty(file_get_contents(LAST_CALLED_JSON))) {
        touch(LAST_CALLED_JSON);
        $json_contents = "";
    } else {
        $json_contents = file_get_contents(LAST_CALLED_JSON);
    }

    # Create empty array if file empty
    if (!empty($json_contents)) {
        $lf  = json_decode($json_contents, true);
    } else {
        $lf = [];
    }

    # If function array exists in JSON file
    if (!var_assert($lf[$function_name])) {
        $lf[$function_name] = [];
    }

    # This endpoint is open
    if (in_array($function_name, OPEN_ENDPOINTS) || $apikey == "all") {
        $apikey_name = "all";
    }

    # If endpoint is NOT open
    if (!in_array($function_name, OPEN_ENDPOINTS)) {
        $apikey_name = apikey_validate($apikey);
    }

    # Invalid API key somehow? This should be an uneccessary check as call_function() already checks it before this is called
    if (!var_assert($apikey_name)) {
        return false;
    }

    $lf[$function_name][$apikey_name] = NOW;
    file_put_contents(LAST_CALLED_JSON, json_encode($lf));
    return true;
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function in_md_array */
/* ────────────────────────────────────────────────────────────────────────── */
function in_md_array($name, $id, $array = API_KEYS) {
    if (!is_array($array)) {
        die(err("The API_KEYS constant isn't a valid array."));
    }
    foreach ($array as $key => $val) {
        if ($val[$name] == $id) {
            return $key;
        }
    }
    return false;
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function apikey_validate */
/* ────────────────────────────────────────────────────────────────────────── */
function apikey_validate($apikey) {
    return in_md_array("key", $apikey);
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function addAPIKey */
/* ────────────────────────────────────────────────────────────────────────── */
function addAPIKey(string $name, string $key, array $options = []) { # array $allowedEndpoints = [], bool $noTimeOut = false, $notify = true) {
    global $apikeys;

    if (empty($options)) {
        $options = APIKEY_DEFAULT_OPTIONS;
    }

    foreach (APIKEY_DEFAULT_OPTIONS as $optName => $optVal) {
        if (!isset($options[$optName])) {
            $options[$optName] = APIKEY_DEFAULT_OPTIONS[$optName];
        }
    }

    $apikeys[$name] = 
    [
        "key" => $key, 
        "options" => $options,
    ];
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function endpointExists */
/* ────────────────────────────────────────────────────────────────────────── */
function endpointExists(string $endpoint) {
    if (function_exists("api_".$endpoint) || function_exists($endpoint)) {
        return true;
    }
    return false;
}
