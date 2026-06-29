<?php

/**
 * Discover and load endpoint PHP files for listing (GUI) and runtime.
 *
 * @return list<string> Absolute paths, sorted.
 */
function discoverEndpointPhpFiles(): array
{
    $root = dirname(__DIR__);
    $files = array_merge(
        glob($root . '/endpoints/*.php') ?: [],
        glob($root . '/endpoints/examples/*.php') ?: []
    );
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    return $files;
}

/** Load endpoint handlers (mirrors discoverEndpointPhpFiles rules). */
function loadEndpointPhpFiles(): void
{
    $root = dirname(__DIR__);
    $endpointsDir = $root . '/endpoints';

    require_once __DIR__ . '/config_loader.php';
    loadPhpConfigDir($endpointsDir, ['my_custom_endpoints.php'], 'my_custom_endpoints.php');

    foreach (glob($endpointsDir . '/examples/*.php') ?: [] as $file) {
        require_once $file;
    }
}

/**
 * @return list<array{name: string, clean: string, params: string}>
 */
function discoverApiFunctionsFromFile(string $file): array
{
    $reflection = discoverApiFunctionsReflection($file);
    $functions = [];
    foreach ($reflection as $item) {
        $functions[] = [
            'name' => $item['name'],
            'clean' => $item['clean'],
            'params' => $item['params'],
        ];
    }

    return $functions;
}

/**
 * @return list<array{name: string, clean: string, params: string, parameters: list<array{name: string, optional: bool, default: mixed|null}>}>
 */
function discoverApiFunctionsReflection(string $file): array
{
    $contents = @file_get_contents($file);
    if ($contents === false) {
        return [];
    }

    if (!preg_match_all('/function\s+(api_[a-zA-Z0-9_]+)\s*\(([^)]*)\)/s', $contents, $matches, PREG_SET_ORDER)) {
        return [];
    }

    $functions = [];
    foreach ($matches as $match) {
        $funcName = $match[1];
        $paramsStr = trim($match[2]);
        $parameters = [];

        if (function_exists($funcName)) {
            try {
                $ref = new ReflectionFunction($funcName);
                $paramParts = [];
                foreach ($ref->getParameters() as $param) {
                    $default = null;
                    $optional = $param->isOptional();
                    if ($param->isDefaultValueAvailable()) {
                        $default = $param->getDefaultValue();
                    }
                    $parameters[] = [
                        'name' => $param->getName(),
                        'optional' => $optional,
                        'default' => $default,
                    ];
                    $part = '$' . $param->getName();
                    if ($optional) {
                        $part .= ' = ' . ($default === null ? 'null' : var_export($default, true));
                    }
                    $paramParts[] = $part;
                }
                $paramsStr = implode(', ', $paramParts);
            } catch (ReflectionException) {
                // keep regex params string
            }
        }

        $functions[] = [
            'name' => $funcName,
            'clean' => preg_replace('/^api_/', '', $funcName),
            'params' => $paramsStr,
            'parameters' => $parameters,
        ];
    }

    usort($functions, static fn(array $a, array $b): int => strcmp($a['clean'], $b['clean']));

    return $functions;
}

/** @return list<string> Short endpoint names (no api_ prefix). */
function discoverAllEndpointNames(): array
{
    $names = [];
    foreach (discoverEndpointPhpFiles() as $file) {
        foreach (discoverApiFunctionsReflection($file) as $func) {
            $names[$func['clean']] = true;
        }
    }

    $list = array_keys($names);
    sort($list, SORT_NATURAL | SORT_FLAG_CASE);

    return $list;
}

/** Whether an endpoint is open (no API key required). */
function endpointIsOpen(string $shortName): bool
{
    if (!function_exists('endpoint_open')) {
        return false;
    }

    return endpoint_open($shortName);
}
