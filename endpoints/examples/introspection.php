<?php

/**
 * Introspection endpoints for GUI and tooling.
 */

function api_getallendpoints(): array
{
    $endpoints = [];
    foreach (discoverEndpointPhpFiles() as $file) {
        foreach (discoverApiFunctionsReflection($file) as $func) {
            $endpoints[] = [
                'endpoint' => $func['clean'],
                'file' => basename($file),
                'open' => endpointIsOpen($func['clean']),
                'parameters' => $func['parameters'],
            ];
        }
    }

    return ['endpoints' => $endpoints];
}

function api_getendpointparams(string $ep): array
{
    $ep = resolveEndpointName($ep);
    $func = 'api_' . $ep;
    if (!function_exists($func)) {
        return ['error' => 'Endpoint not found', 'endpoint' => $ep];
    }

    $ref = new ReflectionFunction($func);
    $parameters = [];
    foreach ($ref->getParameters() as $param) {
        $default = null;
        if ($param->isDefaultValueAvailable()) {
            $default = $param->getDefaultValue();
        }
        $parameters[] = [
            'name' => $param->getName(),
            'optional' => $param->isOptional(),
            'default' => $default,
        ];
    }

    return [
        'endpoint' => $ep,
        'open' => endpointIsOpen($ep),
        'parameters' => $parameters,
    ];
}
