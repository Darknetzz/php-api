<?php

require_once __DIR__ . '/ApiKeyStore.php';

/** @var ApiKeyStore|null */
$GLOBALS['api_key_store'] = null;

function getApiKeyStore(): ?ApiKeyStore
{
    if ($GLOBALS['api_key_store'] instanceof ApiKeyStore) {
        return $GLOBALS['api_key_store'];
    }

    try {
        $GLOBALS['api_key_store'] = ApiKeyStore::createFromConfig();
    } catch (Throwable $e) {
        if (defined('KEY_STORE_DRIVER') && KEY_STORE_DRIVER !== 'php') {
            throw $e;
        }
        $GLOBALS['api_key_store'] = null;
    }

    return $GLOBALS['api_key_store'];
}

/** @return array<string, string> */
function resolveEndpointAliases(): array
{
    if (defined('ENDPOINT_ALIASES') && is_array(ENDPOINT_ALIASES)) {
        return ENDPOINT_ALIASES;
    }
    return [];
}

function resolveEndpointName(string $endpoint): string
{
    $aliases = resolveEndpointAliases();
    $normalized = $endpoint;

    if (isset($aliases[$endpoint])) {
        $target = $aliases[$endpoint];
        return str_starts_with($target, 'api_') ? substr($target, 4) : $target;
    }

    return $normalized;
}

/** Merge API key from HTTP headers into request params. */
function mergeAuthHeaders(array $params): array
{
    $headerKey = null;

    if (!empty($_SERVER['HTTP_APIKEY'])) {
        $headerKey = $_SERVER['HTTP_APIKEY'];
    } elseif (!empty($_SERVER['HTTP_X_API_KEY'])) {
        $headerKey = $_SERVER['HTTP_X_API_KEY'];
    } elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/^Bearer\s+(\S+)/i', $auth, $m)) {
            $headerKey = $m[1];
        }
    }

    if ($headerKey !== null && empty($params['apikey'])) {
        $params['apikey'] = $headerKey;
    }

    return $params;
}

/** @return array<string, string> */
function redactSensitiveParams(array $params): array
{
    $redacted = $params;
    foreach (['apikey', 'api_key', 'key'] as $field) {
        if (isset($redacted[$field])) {
            $redacted[$field] = '[REDACTED]';
        }
    }
    return $redacted;
}

/** @return list<string> */
function globalParamsList(): array
{
    if (defined('GLOBAL_PARAMS') && is_array(GLOBAL_PARAMS)) {
        return GLOBAL_PARAMS;
    }

    return ['apikey', 'endpoint', 'filter', 'filterdata', 'clean', 'compact', 'verbose'];
}
