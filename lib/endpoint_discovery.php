<?php

/**
 * Discover endpoint PHP files for listing (GUI) and loading metadata.
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

/**
 * @return list<array{name: string, clean: string, params: string}>
 */
function discoverApiFunctionsFromFile(string $file): array
{
    $contents = @file_get_contents($file);
    if ($contents === false) {
        return [];
    }

    if (!preg_match_all('/function\s+(api_[a-zA-Z0-9_]+)\s*\(([^)]*)\)/', $contents, $matches, PREG_SET_ORDER)) {
        return [];
    }

    $functions = [];
    foreach ($matches as $func) {
        $functions[] = [
            'name' => $func[1],
            'clean' => preg_replace('/^api_/', '', $func[1]),
            'params' => trim($func[2]),
        ];
    }

    return $functions;
}
