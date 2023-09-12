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
*/


# This example endpoint will just show you your input and a response.
function api_example_endpoint(string $someInput) : array {
    return ["input" => $someInput, "output" => "You had the following input: $someInput"];
}




?>
