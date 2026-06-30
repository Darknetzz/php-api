<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_base.php                             */
/* ────────────────────────────────────────────────────────────────────────── */

require_once __DIR__ . '/lib/bootstrap.php';
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/* 
    This file contains the API base functions - in other words essential functions
    for the API to work. You can of course tweak it however you want, but most of the config
    should be done in `settings` and not here, unless you know what you're doing.
*/



/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function err */
/* ────────────────────────────────────────────────────────────────────────── */
function err(string $text, int $statusCode = 500, bool $fatal = true) {
    // Security: Sanitize error messages in production to avoid information disclosure
    $sanitized_text = $text;
    if (defined('PRODUCTION_MODE') && PRODUCTION_MODE === true) {
        // In production, don't expose detailed error messages
        if ($statusCode >= 500) {
            $sanitized_text = "Internal server error";
        }
    }
    
    log_write($text, 'verbose');
    http_response_code($statusCode);
    return json_encode(
        [
            "httpCode" => $statusCode,
            "status" => "ERROR",
            "data" => $sanitized_text,
        ]
    );
}


/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function var_assert */
/* ────────────────────────────────────────────────────────────────────────── */
function var_assert(mixed &$var, mixed $assertVal = false, bool $lazy = false) : bool {
    if (!isset($var)) {
        return false;
    }

    if ($assertVal != false || func_num_args() > 1) {
        if ($var === '' || $var === null) {
            return false;
        }

        if ($lazy != false) {
            return $var == $assertVal;
        }

        return $var === $assertVal;
    }

    if ($var === '' || $var === null || $var === false) {
        return false;
    }
    if (is_array($var) && $var === []) {
        return false;
    }
    if ($var === 0 || $var === 0.0 || $var === '0') {
        return false;
    }

    return true;
}

/** True when a request flag is enabled (compact, verbose, clean, etc.). */
function requestFlagEnabled(mixed $value): bool
{
    if (!isset($value) || $value === '' || $value === null) {
        return false;
    }
    if ($value === false || $value === 0 || $value === '0' || $value === 'false') {
        return false;
    }
    if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
        return true;
    }

    return false;
}



/* ────────────────────────────────────────────────────────────────────────── */
/*                                 Get user IP                                */
/* ────────────────────────────────────────────────────────────────────────── */
function userIP() {
    // Security: Only trust X-Forwarded-For if from trusted proxy
    // Default to REMOTE_ADDR which is more reliable and harder to spoof
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Only use X-Forwarded-For if configured to trust proxies
        if (defined('TRUST_PROXY') && TRUST_PROXY === true && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Validate and sanitize X-Forwarded-For
            $forwarded = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
            
            // Determine validation flags based on configuration
            $filter_flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;
            if (!defined('ALLOW_PRIVATE_IPS') || ALLOW_PRIVATE_IPS === false) {
                $filter_flags |= FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
            }
            
            // Validate IP address
            if (filter_var($forwarded, FILTER_VALIDATE_IP, $filter_flags)) {
                $ip = $forwarded;
            }
        }
        
        return $ip;
    }
    die(err("Unable to determine IP"));
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                            Validate JSON decode                            */
/* ────────────────────────────────────────────────────────────────────────── */
function validate_json_decode(string $json_string, bool $allow_empty = true) {
    // Handle empty string case
    if ($json_string === '') {
        return $allow_empty ? [] : null;
    }
    
    $result = json_decode($json_string, true);
    
    // Check for JSON decode errors
    if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    return $result;
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
    if (WHITELIST_MODE == True) {
        foreach (OPEN_ENDPOINTS as $openep) {
            if ($endpoint == $openep || 'api_'.$endpoint == $openep) {
                return True;
            }
        }
    }
    if (WHITELIST_MODE == False) {
        $protected = array_map(function ($e) {
            return (strpos($e, 'api_') === 0) ? $e : 'api_' . $e;
        }, PROTECTED_ENDPOINTS);
        $normalized = (strpos($endpoint, 'api_') === 0) ? $endpoint : 'api_' . $endpoint;
        return !in_array($normalized, $protected, true);
    }
    return false;
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                              NOTE: log_write()                             */
/* ────────────────────────────────────────────────────────────────────────── */
function rotateLogFileIfNeeded(string $logFile): void
{
    $maxLines = defined('LOG_MAXLINES') ? (int) LOG_MAXLINES : 1000;
    $keep = defined('LOG_ROTATE_KEEP') ? (int) LOG_ROTATE_KEEP : 5;
    if ($maxLines < 1 || $keep < 1) {
        return;
    }

    if (!is_file($logFile)) {
        return;
    }

    $lineCount = 0;
    $fh = fopen($logFile, 'r');
    if ($fh === false) {
        return;
    }
    while (fgets($fh) !== false) {
        $lineCount++;
    }
    fclose($fh);

    if ($lineCount < $maxLines) {
        return;
    }

    $oldest = $logFile . '.' . $keep;
    if (is_file($oldest)) {
        unlink($oldest);
    }

    for ($i = $keep - 1; $i >= 1; $i--) {
        $from = $logFile . '.' . $i;
        $to = $logFile . '.' . ($i + 1);
        if (is_file($from)) {
            rename($from, $to);
        }
    }

    if (is_file($logFile)) {
        rename($logFile, $logFile . '.1');
    }
}

function log_write($txt, $level = 'info') {
    if (!defined('LOG_ENABLE') || LOG_ENABLE === false) {
        return;
    }
    try {
        global $apikey_logging;
        if (!isset($apikey_logging) || $apikey_logging !== true) {
            return;
        }
        $level        = strtoupper($level);
        $log_level    = (defined('LOG_LEVEL')  ? strtoupper(LOG_LEVEL) : 'INFO');
        $log_file     = (defined('LOG_FILE')   ? LOG_FILE      : 'api.log');

        if (!in_array($log_level, array_keys(LOG_LEVELS))) {
            die(err("You have specified a LOG_LEVEL that doesn't exist in the LOG_LEVELS array: ".$log_level." not in ".implode(', ', array_keys(LOG_LEVELS))));
        }

        if (!array_key_exists($level, LOG_LEVELS)) {
            $level = 'INFO';
        }

        $thisLevel = LOG_LEVELS[$level];
        $myLevel   = LOG_LEVELS[$log_level];

        if ($myLevel < $thisLevel) {
            return;
        }

        rotateLogFileIfNeeded($log_file);

        if (!file_exists($log_file)) {
            $dir = dirname($log_file);
            if ($dir !== '.' && $dir !== '' && !is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
                die(err("Unable to create log directory: $dir"));
            }
            $testwrite = file_put_contents($log_file, '');
            if ($testwrite === false && !is_file($log_file)) {
                die(err("Function log_write was unable to write to log. Check the permissions of the log file: $log_file"));
            }
        }

        $padding   = max(array_map('strlen', LOG_LEVELS));
        $plevel    = str_pad("[$level] ", $padding, ' ');
        $prefix    = date('Y-m-d H:i:s') . ' ' . $plevel . userIP() . ': ';

        // Security: Sanitize log message to prevent log injection attacks
        $sanitized_txt = str_replace(["\n", "\r", "\0"], ' ', $txt);
        $line = $prefix . $sanitized_txt . "\n";

        $fh = fopen($log_file, 'a');
        if ($fh === false) {
            die(err("Unable to open log file for writing: $log_file"));
        }
        if (flock($fh, LOCK_EX)) {
            fwrite($fh, $line);
            flock($fh, LOCK_UN);
        }
        fclose($fh);
        return;
    } catch(Throwable $t) {
        die(err("Unable to write to log: $t"));
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
        "allParamNames",
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

    # Not sure if this ever happens? I think $requiredParams always will be a string
    if (!empty($allParamNames)) {
        $csParams = "Parameters for this endpoint: ".implode(', ',$allParamNames);
    } else {
        $csParams = "Parameters for this endpoint: none";
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
                "Wait a minute, you specified $paramsCleanCount, but this endpoint requires $requiredParamCount of them... $csParams",
                "Alright now you are confusing me... I need $requiredParamCount parameters for this function to work, but for some reason you gave me only $paramsCleanCount. $csParams",
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

    $pretty_print = JSON_UNESCAPED_UNICODE;
    if (!requestFlagEnabled($params['compact'] ?? null)) {
        $pretty_print = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;
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
        $filterdata = array_map('trim', explode(",", $params['filterdata']));

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
    if (requestFlagEnabled($params['verbose'] ?? null)) {
        if (!var_assert($verboseInfo)) {
            $return['data']['response']['verboseInfo'] = "The verbose flag was present, but the content is empty.";
        } else {
            $return['data']['response']['verboseInfo'] = $verboseInfo;
        }
    }
    # /verboseinfo


    # clean
    if (requestFlagEnabled($params['clean'] ?? null)) {
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
        /*                            Not an open endpoint                            */
        /* ────────────────────────────────────────────────────────────────────────── */
        if (!endpoint_open($endpoint)) {

            if (!var_assert($params['apikey'])) {
                die(err("Missing required API key."));
            }

            # API key was provided
            $apikey = null;
            foreach (['apikey', 'api_key', 'key'] as $candidate) {
                if (isset($params[$candidate]) && $params[$candidate] !== '') {
                    $apikey = $params[$candidate];
                    break;
                }
            }
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
            $apikey_options = ApiKeyStore::mergeDefaultOptions(API_KEYS[$valid_apikey]['options']);

            if (!isset($apikey_options['log_write'])) {
                die(err("Option 'log_write' not specified for this API key."));
            }
            $GLOBALS['apikey_logging'] = (bool) $apikey_options['log_write'];

            # Check if this key is specifically disallowed
            if (in_array($endpoint, $apikey_options["disallowedEndpoints"])) {
                die(err("You are blacklisted/disallowed from using this endpoint."));
            }
        
            # Check allowed endpoints
            if (!in_array("*", $apikey_options["allowedEndpoints"]) && !in_array($endpoint, $apikey_options["allowedEndpoints"])) {
                die(err("You do not have access to this endpoint.", 403));
            }

            # Sleep ($sleep is configured in seconds)
            if (!empty($apikey_options["sleep"])) {
                $sleep = $apikey_options["sleep"];
                if ($sleep > 0) {
                    usleep((int) ($sleep * 1000000));
                }
            }

        } else {
            # Open endpoint: merge defaults so cooldown/logging checks have all keys
            $apikey_options = ApiKeyStore::mergeDefaultOptions([]);
            $GLOBALS['apikey_logging'] = (bool) ($apikey_options['log_write'] ?? false);
            $valid_apikey = null;
        }
        /* ────────────────────────────────────────────────────────────────────────── */

        $logParams = redactSensitiveParams($params);
        log_write("Attempting to call function $func with parameters: ".json_encode($logParams), 'verbose');

        $paramsClean = [];
        foreach ($params as $paramName => $paramValue) {
                if (!in_array($paramName, globalParamsList(), true)) {
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
            
            if ($secondsSinceLastCalled === false && ($apikey_options['noTimeOut'] ?? false) === false) {
                die(err("Function secondsSinceLastCalled() failed. Please stop spamming this API.", 403));
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
                    "allParamNames"         => $allParamNames,
                    "allParamCount"         => $allParamCount,
                    "paramsCleanCount"      => $paramsCleanCount,
                    "requiredParamCount"    => $requiredParamCount,
                ]));
        }

        # Error: Too quick!
        $cooldown = (int) ($apikey_options['cooldown'] ?? COOLDOWN_TIME);
        if ($secondsSinceLastCalled < $cooldown && ($apikey_options['noTimeOut'] ?? false) === false) {
            return err(funnyResponse(
                'COOLDOWN', [
                    'endpoint' => $func,
                    'secondsSinceLastCalled' => $secondsSinceLastCalled,
                    'secondsToWait' => ($cooldown - $secondsSinceLastCalled),
                    'noTimeOut' => $apikey_options['noTimeOut'],
                ]), 403);
        }

        $functionCall = $functionObject->invokeArgs($paramsClean);
        updateLastCalled($func, $valid_apikey);

        if (!$functionCall) {

            return err("The endpoint '$func' returned an empty/false response.");
        }

        if (NOTIFY_API === true) {
            $notify = false;
            if (!empty($valid_apikey) && isset(API_KEYS[$valid_apikey]['options']['notify'])) {
                $notify = API_KEYS[$valid_apikey]['options']['notify'] === true;
            }
            if (!empty($valid_apikey) && $notify) {
                api_sms(NOTIFY_NUMBER, "API Called by $valid_apikey: $params[endpoint]");
            } elseif (empty($valid_apikey)) {
                api_sms(NOTIFY_NUMBER, "API Called by ".userIP().": $params[endpoint]");
            }
        }

        return api_response(status: "OK", data: ["response" => $functionCall, "verboseInfo" => $verboseInfo]);
    } catch (Throwable $e) {
        // Security: Log full exception but return sanitized message
        $full_error = "Exception encountered while calling endpoint $func. ".$e->getMessage();
        log_write($full_error, 'verbose');
        
        $error_msg = "Exception encountered while calling endpoint $func.";
        if (defined('PRODUCTION_MODE') && PRODUCTION_MODE === false) {
            // Only show detailed exception in non-production
            $error_msg .= " ".$e->getMessage();
        }
        
        return err($error_msg);
    }
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function secondsSinceLastCalled */
/* ────────────────────────────────────────────────────────────────────────── */
function ensureLastCalledJsonFile(): string
{
    $path = LAST_CALLED_JSON;
    $dir = dirname($path);
    if ($dir !== '.' && $dir !== '' && !is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        die(err('Unable to create last-called directory: ' . $dir));
    }
    if (!file_exists($path)) {
        file_put_contents($path, '{}');
    }

    return $path;
}

/** @return array<string, mixed> */
function readLastCalledStore(): array
{
    ensureLastCalledJsonFile();
    $path = LAST_CALLED_JSON;

    $fh = fopen($path, 'r');
    if (!$fh) {
        die(err('Unable to open last called file for reading'));
    }

    if (!flock($fh, LOCK_SH)) {
        fclose($fh);
        die(err('Unable to acquire lock on last called file'));
    }

    $json_contents = stream_get_contents($fh);
    flock($fh, LOCK_UN);
    fclose($fh);

    if ($json_contents === false || $json_contents === '') {
        return [];
    }

    $lf = validate_json_decode($json_contents);
    if ($lf === null) {
        log_write('Invalid JSON in last called file; treating as empty.', 'warning');
        return [];
    }

    return $lf;
}

/** @param callable(array<string, mixed>): void $mutator */
function mutateLastCalledStore(callable $mutator): void
{
    ensureLastCalledJsonFile();
    $path = LAST_CALLED_JSON;

    $fh = fopen($path, 'c+');
    if (!$fh) {
        die(err('Unable to open last called file for writing'));
    }

    if (!flock($fh, LOCK_EX)) {
        fclose($fh);
        die(err('Unable to acquire lock on last called file'));
    }

    $json_contents = stream_get_contents($fh);
    if ($json_contents === false || $json_contents === '') {
        $lf = [];
    } else {
        $lf = validate_json_decode($json_contents);
        if ($lf === null) {
            log_write('Invalid JSON in last called file; resetting store.', 'warning');
            $lf = [];
        }
    }

    $mutator($lf);

    $encoded = json_encode($lf, JSON_THROW_ON_ERROR);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, $encoded);
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
}

/** @param array<string, mixed> $lf */
function writeLastCalledStore(array $lf): void
{
    mutateLastCalledStore(static function (array &$store) use ($lf): void {
        $store = $lf;
    });
}

function secondsSinceLastCalled($function_name, $valid_apikey = null) {
    try {
        $lf = readLastCalledStore();
        
        # This endpoint is open
        if (endpoint_open($function_name) || $valid_apikey == null) {
            $valid_apikey = userIP();
        }

        # Somehow the apikey_name is still empty
        if (empty($valid_apikey)) {
            die(err("updateLastCalled: This endpoint is either not open, or the api key you provided is null/invalid. IP: ".userIP()." - Name: $valid_apikey"));
        }

        if (!var_assert($lf[$function_name])) {
            $lastcalled = time() - COOLDOWN_TIME;
        } elseif (!var_assert($lf[$function_name][$valid_apikey])) {
            $lastcalled = time() - COOLDOWN_TIME;
        } else {
            $lastcalled = $lf[$function_name][$valid_apikey];
        }

        return time() - $lastcalled;

    } catch (Throwable $t) {
        die(err($t));
    }
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function updateLastCalled */
/* ────────────────────────────────────────────────────────────────────────── */
function updateLastCalled($function_name, $valid_apikey = null) {
    try {
        $resolvedKey = $valid_apikey;
        if (endpoint_open($function_name) || $resolvedKey == null) {
            $resolvedKey = userIP();
        }

        if (empty($resolvedKey)) {
            die(err("updateLastCalled: This endpoint is either not open, or the api key you provided is null/invalid. IP: ".userIP()." - Name: $resolvedKey"));
        }

        mutateLastCalledStore(static function (array &$lf) use ($function_name, $resolvedKey): void {
            if (!var_assert($lf[$function_name])) {
                $lf[$function_name] = [];
            }
            $lf[$function_name][$resolvedKey] = time();
        });

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
        // Security: Use hash_equals for constant-time comparison to prevent timing attacks
        if (isset($val[$name]) && hash_equals((string)$val[$name], (string)$id)) {
            return $key;
        }
    }
    return false;
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function apikey_validate */
/* ────────────────────────────────────────────────────────────────────────── */
function apikey_validate($apikey) {
    $store = getApiKeyStore();
    if ($store instanceof ApiKeyStore) {
        $name = $store->validate((string) $apikey);
        return $name ?? false;
    }
    return in_md_array("key", $apikey);
}

/* ────────────────────────────────────────────────────────────────────────── */
/*                                  NOTE: Function addAPIKey */
/* ────────────────────────────────────────────────────────────────────────── */
function addAPIKey(string $name, string $key, array $options = []) {
    global $apikeys;

    $options = ApiKeyStore::mergeDefaultOptions($options);

    $store = getApiKeyStore();
    if ($store instanceof ApiKeyStore) {
        if (!$store->exists($name)) {
            $store->create($name, $key, $options);
        }
    }

    $apikeys[$name] = [
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
