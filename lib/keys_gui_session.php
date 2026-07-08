<?php

function keysGuiEnabled(): bool
{
    return defined('ENABLE_API_KEYS_GUI')
        && ENABLE_API_KEYS_GUI === true
        && defined('KEY_STORE_DRIVER')
        && KEY_STORE_DRIVER !== 'php';
}

function keysGuiAdminPassword(): string
{
    $env = getenv('API_KEYS_ADMIN_PASSWORD');
    if ($env !== false && $env !== '') {
        return $env;
    }

    return defined('API_KEYS_ADMIN_PASSWORD') ? (string) API_KEYS_ADMIN_PASSWORD : '';
}

function keysGuiConfigureSession(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    session_start();
}

function keysGuiIsAuthenticated(): bool
{
    return !empty($_SESSION['keys_gui_auth']);
}

/** @return array<string, string> */
function keysGuiVaultGetAll(): array
{
    $vault = $_SESSION['keys_gui_vault'] ?? [];
    if (!is_array($vault)) {
        return [];
    }

    $clean = [];
    foreach ($vault as $name => $key) {
        if (!is_string($name) || !is_string($key) || $name === '' || $key === '') {
            continue;
        }
        $clean[$name] = $key;
    }

    return $clean;
}

function keysGuiVaultGet(string $name): ?string
{
    $vault = keysGuiVaultGetAll();

    return $vault[$name] ?? null;
}

function keysGuiVaultSet(string $name, string $plaintext): void
{
    if ($name === '' || $plaintext === '') {
        return;
    }

    if (!isset($_SESSION['keys_gui_vault']) || !is_array($_SESSION['keys_gui_vault'])) {
        $_SESSION['keys_gui_vault'] = [];
    }

    $_SESSION['keys_gui_vault'][$name] = $plaintext;
}

function keysGuiVaultForget(string $name): void
{
    if (!isset($_SESSION['keys_gui_vault']) || !is_array($_SESSION['keys_gui_vault'])) {
        return;
    }

    unset($_SESSION['keys_gui_vault'][$name]);
}
