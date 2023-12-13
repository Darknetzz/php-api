<?php
/* ────────────────────────────────────────────────────────────────────────── */
/*                         my_custom_api_keys.php                             */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/*
    Define your API keys here, using addAPIKey(name, key, options)

    Parameters:
    name             = the name of the API key (just for identification)
    key              = the actual api key
    options          = this is where the key-specific customization comes into play, see below

available options:
    | NAME                | DEFAULT VALUE | DESCRIPTION                                                                                             |
    | :------------------ | :------------ | :------------------------------------------------------------------------------------------------------ |
    | allowedEndpoints    | ["*"]         | Endpoints this key has access to. If there is a * in the array the key will be unrestricted.            |
    | disallowedEndpoints | []            | Endpoints this key specifically doesn't have access to, will override allowedEndpoints                  |
    | noTimeOut           | false         | Specify if this key can bypass the timeout                                                              |
    | cooldown            | COOLDOWN_TIME | Time in seconds this key has to wait between API calls (COOLDOWN_TIME is specified in api_settings.php) |
    | sleep               | SLEEP_TIME    | Time in seconds this key has to wait for a response                                                     |
    | notify              | true          | Whether or not to notify the owner of this API when an endpoint is used.                                |
    | log_write           | true          | Whether or not to log requests with this API key to file (requires LOG_ENABLE)                          |
*/




$apikeys = [];

# Uncomment and tweak it to your likings.
# This will enable the API key with the options provided.
# Please, for the love of god, do not reuse these keys, generate your own here:
# https://ubuntu.roste.org/rand/#rsgen

/*
addAPIKey(
    name: "MasterKey",
    key: "nrTv7xL6qyoOhWH7VBoh0Fs9JwChcoBNLhj1Us7l7zQKENBT0N8cZwDwB48YPdRL",
    options: [
        "allowedEndpoints" => ["testEndpoint", "anotherEndpoint"], 
        "noTimeOut"        => true           , 
        "notify"           => false          , 
    ]
);


addAPIKey(
    name: "TestKey",
    key: "sHpkjwnos3YsxN5UOZY9xlAPDm2BVH0V81hOksVcZJazOYCOEMhwUveeZeYaGbQd",
    options: [
        "allowedEndpoints" => ["testEndpoint", "anotherEndpoint"], 
        "noTimeOut"        => true           , 
        "notify"           => false          , 
    ]
);
*/

?>
