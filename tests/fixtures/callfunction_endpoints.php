<?php

if (!function_exists('api_cf_open')) {
    function api_cf_open(): array
    {
        return ['open' => true];
    }
}

if (!function_exists('api_cf_protected')) {
    function api_cf_protected(): array
    {
        return ['protected' => true];
    }
}
