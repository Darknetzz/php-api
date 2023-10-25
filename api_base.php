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
    log_write($text, 'verbose');
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
/*                                 Get user IP                                */
/* ────────────────────────────────────────────────────────────────────────── */
function userIP() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }
    die(err("Unable to determine IP"));
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                               Close file                                   */
/* ────────────────────────────────────────────────────────────────────────── */
function fh_close(mixed &$fh) {
    $tries = 5;
    $i     = 0;
    while (is_resource($fh)) {
        if ($i > $tries) {
            break;
        }
        
        fclose($fh);
        $i++;
    }
    
    return !is_resource($fh);
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                                endpoint_open                               */
/* ────────────────────────────────────────────────────────────────────────── */
function endpoint_open(string $endpoint) {
    foreach (OPEN_ENDPOINTS as $openep) {
        if ($endpoint == $openep || 'api_'.$endpoint == $openep) {
            return true;
        }
    }
    return false;
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                              NOTE: log_write()                             */
/* ────────────────────────────────────────────────────────────────────────── */
function log_write($txt, $level = 'info') {
    if (isset($log_enable) && $log_enable !== false) {
    try {
        global $apikey_logging;
        if (!isset($apikey_logging) || !$apikey_logging === true) {
            return;
        }
        $level        = strtoupper($level);
        $log_level    = (defined('LOG_LEVEL')  ? strtoupper(LOG_LEVEL) : 'INFO');
        $log_enable   = (defined('LOG_ENABLE') ? LOG_ENABLE    : null);
        $log_file     = (defined('LOG_FILE')   ? LOG_FILE      : 'api.log');
        $log_maxlines = (defined('LOG_MAXLINES') ? LOG_MAXLINES: 1000);

        if (!in_array($log_level, array_keys(LOG_LEVELS))) {
            die(err("You have specified a LOG_LEVEL that doesn't exist in the LOG_LEVELS array: ".$log_level." not in ".implode(', ', array_keys(LOG_LEVELS))));
        }

        $thisLevel = LOG_LEVELS[$level];
        $myLevel   = LOG_LEVELS[$log_level];

        if ($myLevel < $thisLevel) {
            echo "$myLevel < $thisLevel";
            return;
        }

        if (!file_exists($log_file)) {
            touch($log_file);
            $testwrite = file_put_contents($log_file, 'Testing write access');
    
            if (!$testwrite && !file_exists($log_file)) {
                die(err("Function log_write was unable to write to log. Check the permissions of the log file: $log_file"));
            }
    
            unlink($log_file);
            $currLog = "Logfile created at ".date('Y-m-d H:i:s')."\n";
        } else {
            $padding   = max(array_map('strlen', LOG_LEVELS));
            $plevel    = str_pad("[$level] ", $padding, ' ');
            $prefix    = $plevel." ".userIP().": ";

            $lines     = file($log_file);
            $remainder = $log_maxlines - count($lines);
            $currLine  = 0;
            while ($remainder < 0) {
                unset($lines[$currLine]);
                $currLine++;
                $remainder++;
            }

            // $fh      = fopen($log_file, 'w+');
            // $currLog = fread($fh);
            // $lines   = 0;
            // while (!feof($fh)) {
            //     $line = fgets($fh);
            //     $lines++;
            // }
            // while ($log_maxlines - $lines > 0) {
                
            //     $lines--;
            // }
        }

        $writeLog = $currLog.$prefix.$txt."\n";

        $fh = fopen($log_file, 'w+');
        fwrite($fh, $writeLog);
        fh_close($fh);
        return;
    } catch(Throwable $t) {
        die(err("Unable to write to log: $t"));
    }
}
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
        "noTimeOut",
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
    
    global $_REQUEST;
    $params = $_REQUEST;

    log_write("api_response(): The API responded with a status of $status.");

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

    log_write("Attempting to call function $func with parameters: ".implode($params), 'verbose');

    try {
        
        /* ────────────────────────────────────────────────────────────────────────── */
        /*                               Initial checks                               */
        /* ────────────────────────────────────────────────────────────────────────── */

        # defaults
        $apikey         = null;
        $apikey_options = null;
        $apikey_logging = null;
        $valid_apikey   = null;

        # Check for endpoint param
        if (!var_assert($params["endpoint"])) {
            die(err("No endpoint was provided"));
        }

        $endpoint = $params["endpoint"];

        # Verify existing endpoint
        if (!function_exists($func)) {
            die(err("Invalid endpoint '$func'"));
        }



        /* ────────────────────────────────────────────────────────────────────────── */

        /* ────────────────────────────────────────────────────────────────────────── */
        /*                            Not an open endpoint                            */
        /* ────────────────────────────────────────────────────────────────────────── */
        if (!endpoint_open($endpoint)) {

            if (!var_assert($params['apikey'])) {
                die(err("Missing required API key."));
            }

            # API key was provided
            $apikey = $params['apikey'];
            $valid_apikey = apikey_validate($apikey);

            # Invalid API key
            if (!$valid_apikey || empty($valid_apikey)) {
                die(err("Invalid API key", 403));
            }


            # The API key default options are given to the API key when created: addAPIKey function
            # You don't need to set defaults here, just check it directly in API_KEYS[$valid_apikey]['options']

            # Get options from this API key
            if (empty(API_KEYS[$valid_apikey]['options'])) {
                die(err("The options for this API key cannot be found"));
            }
            $apikey_options = API_KEYS[$valid_apikey]['options'];

            # Get logging option for this API key
            if (empty($apikey_options['log_write'])) {
                die(err("Option 'log_write' not specified for this API key."));
            }
            $apikey_logging = $apikey_options['log_write']; # this is used in log_write

            if (in_array($endpoint, $apikey_options["disallowedEndpoints"])) {
                die(err("You are blacklisted/disallowed from using this endpoint."));
            }
        
            if (!in_array("*", $apikey_options["allowedEndpoints"]) && !in_array($endpoint, $apikey_options["allowedEndpoints"])) {
                die(err("You do not have access to this endpoint.", 403));
            }
        }
        /* ────────────────────────────────────────────────────────────────────────── */

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
            $secondsSinceLastCalled = secondsSinceLastCalled($func, $valid_apikey);
            
            if (!$secondsSinceLastCalled && $apikey_options['noTimeOut'] === false) {
                die(err("Function secondsSinceLastCalled() failed. Please stop spamming this API."));
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
        if ($secondsSinceLastCalled < COOLDOWN_TIME && $apikey_options["noTimeOut"] === false) {
            return err(funnyResponse(
                "COOLDOWN", [
                    "endpoint" => $func,
                    "secondsSinceLastCalled" => $secondsSinceLastCalled,
                    "secondsToWait" => (COOLDOWN_TIME - $secondsSinceLastCalled),
                    "noTimeOut" => $apikey_options["noTimeOut"],
                ]));
            // return err("The endpoint '$func' was called a mere ".$secondsSinceLastCalled." seconds ago! Please wait another ".(COOLDOWN_TIME - $secondsSinceLastCalled)." seconds.");
        }

        $functionCall = $functionObject->invoke(...$paramsClean);
        updateLastCalled($func, $valid_apikey);

        if (!$functionCall) {

            return err("The endpoint '$func' returned an empty/false response.");
        }

        if (NOTIFY_API === true) {
            if (API_KEYS[$valid_apikey]["options"]["notify"] === true) {
                api_sms(NOTIFY_NUMBER, "API Called by $valid_apikey: $params[endpoint]");
            }
            if (empty($valid_apikey)) {
                api_sms(NOTIFY_NUMBER, "API Called by ".userIP().": $params[endpoint]");
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
function secondsSinceLastCalled($function_name, $valid_apikey = null) {
    try {

        if (!file_exists(LAST_CALLED_JSON)) {
            touch(LAST_CALLED_JSON);
        }

        $json_contents = file_get_contents(LAST_CALLED_JSON);
        $lf = json_decode($json_contents, true);
        
        # This endpoint is open
        if (endpoint_open($function_name) || $valid_apikey == null) {
            $valid_apikey = userIP();
        }

        # Somehow the apikey_name is still empty
        if (empty($valid_apikey)) {
            die(err("updateLastCalled: This endpoint is either not open, or the api key you provided is null/invalid. IP: ".userIP()." - Name: $valid_apikey"));
        }

        if (!var_assert($lf[$function_name])) {
            $lastcalled = (NOW - COOLDOWN_TIME);
        } elseif (!var_assert($lf[$function_name][$valid_apikey])) {
            $lastcalled = (NOW - COOLDOWN_TIME);
        } else {
            $lastcalled = $lf[$function_name][$valid_apikey];
        }

        return (NOW - $lastcalled);

    } catch (Throwable $t) {
        # This should not return false, makes it incredibly hard to troubleshoot permission error.
        // return false;
        die(err($t));
    }
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function updateLastCalled */
/* ────────────────────────────────────────────────────────────────────────── */
function updateLastCalled($function_name, $valid_apikey = null) {
    try {

        if (!file_exists(LAST_CALLED_JSON)) {
            touch(LAST_CALLED_JSON);
        }

        $json_contents = file_get_contents(LAST_CALLED_JSON);

        # Create empty array if file empty
        if (empty($json_contents)) {
            $json_contents = "";
            $lf            = [];
        } else {
            $lf  = json_decode($json_contents, true);
        }

        # If function array doesn't exists in JSON file, create it
        if (!var_assert($lf[$function_name])) {
            $lf[$function_name] = [];
        }

        # This endpoint is open
        if (endpoint_open($function_name) || $valid_apikey == null) {
            $valid_apikey = userIP();
        }

        # Somehow the valid_apikey is still empty
        if (empty($valid_apikey)) {
            die(err("updateLastCalled: This endpoint is either not open, or the api key you provided is null/invalid. IP: ".userIP()." - Name: $valid_apikey"));
        }

        $lf[$function_name][$valid_apikey] = NOW;

        $fh = fopen(LAST_CALLED_JSON, 'w+');
        fwrite($fh, json_encode($lf));
        fh_close($fh);

        return true;

    } catch (Throwable $t) {
        die(err($t));
    }
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
    $name = in_md_array("key", $apikey);
    return $name;
    if (!$name) {
        die(err("Invalid key $apikey"));
    }
    return $name;
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function addAPIKey */
/* ────────────────────────────────────────────────────────────────────────── */
function addAPIKey(string $name, string $key, array $options = []) { # array $allowedEndpoints = [], bool $noTimeOut = false, $notify = true) {
    global $apikeys;

    if (empty($options)) {
        $options = APIKEY_DEFAULT_OPTIONS;
    }

    foreach ($options as $optName => $optVal) {

    }

    foreach (APIKEY_DEFAULT_OPTIONS as $optName => $optVal) {
        if (!isset($options[$optName])) {
            $options[$optName] = APIKEY_DEFAULT_OPTIONS[$optName];
            // echo "[DEFAULT $name: $optName = $options[$optName]]";
        } else {
            $options[$optName] = $options[$optName];
            // echo "[CUSTOM $name: $optName = $options[$optName]]";
        }
    }

    $apikeys[$name] = 
    [
        "key" => $key, 
        "options" => $options,
    ];
    // echo $name;
    // print_r($options);
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
