<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_endpoints.php                        */
/* ────────────────────────────────────────────────────────────────────────── */
/* ──────── Made with ❤️ by darknetzz @ https://github.com/darknetzz ──────── */
/* ────────────────────────────────────────────────────────────────────────── */
/*
    Here is where you build your endpoints! Some examples will follow.

    Some important rules:
    - The endpoint function names MUST be prefixed with api_
    - Endpoints should ALWAYS return an array, regardless of what or how many things you want to return.
    - Endpoint-specific errors can be handled in the function, but please return an array anyway.


| Global parameters | Description                                | Default | Possible values        |
| :---------------- | :----------------------------------------- | :------ | :--------------------- |
| filter            | Filters response to only data or status    | None    | httpCode, data, status |
| filterdata        | Filters response data to a single field    | None    | Depends on endpoint    |
| clean             | Omits HTTP status code and text            | false   | true, false            |
| compact           | Disables JSON pretty print                 | false   | true, false            |
| verbose           | Prints out a lot of additional information | false   | true, false            |

*/


# This example endpoint will just show you your input and a response.
function api_example_endpoint(string $someInput) : array {
    return ["input" => $someInput, "output" => "You had the following input: $someInput"];
}

# Another example with optional $append parameter.
function api_echo(string $input, string $append = "Optional parameter") {
    return ["This can be anything." => "You typed $input. But the second parameter is $append."];
}

# This endpoint will return the user's IP address.
function api_ip() {
    $ip = (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
    return ["ip" => $ip];
}

?>
