<?php

require_once __DIR__ . '/api_settings.php';
require_once __DIR__ . '/lib/bootstrap.php';

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

function keysGuiVerifyPassword(string $input, string $stored): bool
{
    if ($stored === '' || $input === '') {
        return false;
    }

    if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$') || str_starts_with($stored, '$argon2')) {
        return password_verify($input, $stored);
    }

    return hash_equals($stored, $input);
}

function keysGuiH(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function keysGuiCsrfToken(): string
{
    if (empty($_SESSION['keys_gui_csrf'])) {
        $_SESSION['keys_gui_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['keys_gui_csrf'];
}

function keysGuiValidateCsrf(): bool
{
    $token = $_POST['csrf'] ?? '';
    $expected = $_SESSION['keys_gui_csrf'] ?? '';

    return $expected !== '' && hash_equals($expected, $token);
}

function keysGuiFlashSet(string $type, string $message): void
{
    $_SESSION['keys_gui_flash'] = ['type' => $type, 'message' => $message];
}

function keysGuiFlashGet(): ?array
{
    if (empty($_SESSION['keys_gui_flash'])) {
        return null;
    }

    $flash = $_SESSION['keys_gui_flash'];
    unset($_SESSION['keys_gui_flash']);

    return $flash;
}

function keysGuiRenderPage(string $title, string $body): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= keysGuiH($title) ?></title>
    <link rel="stylesheet" href="https://ubuntu.roste.org/_assets/tabler.min.css">
    <script src="https://ubuntu.roste.org/_assets/tabler.min.js"></script>
</head>
<body data-bs-theme="dark">
    <div class="container pt-5 pb-5">
        <?= $body ?>
    </div>
</body>
</html>
    <?php
}

if (!keysGuiEnabled()) {
    http_response_code(404);
    keysGuiRenderPage('Not Found', '<p class="text-secondary">Not found.</p>');
    exit;
}

if (keysGuiAdminPassword() === '') {
    http_response_code(503);
    keysGuiRenderPage('Unavailable', '<p class="text-secondary">API keys admin is not configured.</p>');
    exit;
}

session_start();

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: api_keys_gui.php');
    exit;
}

$authenticated = !empty($_SESSION['keys_gui_auth']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    if (keysGuiVerifyPassword((string) ($_POST['password'] ?? ''), keysGuiAdminPassword())) {
        session_regenerate_id(true);
        $_SESSION['keys_gui_auth'] = true;
        header('Location: api_keys_gui.php');
        exit;
    }
    $loginError = 'Invalid credentials.';
}

if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && keysGuiValidateCsrf()) {
    $store = ApiKeyStore::createFromConfig();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $endpoint = trim((string) ($_POST['endpoint'] ?? ''));
        $noTimeout = isset($_POST['no_timeout']);

        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $name)) {
            keysGuiFlashSet('danger', 'Invalid key name. Use letters, numbers, underscores, and hyphens only.');
        } elseif ($store->exists($name)) {
            keysGuiFlashSet('danger', "Key name already exists: $name");
        } else {
            $options = [];
            if ($endpoint !== '') {
                $options['allowedEndpoints'] = [$endpoint];
            }
            if ($noTimeout) {
                $options['noTimeOut'] = true;
            }
            $key = ApiKeyStore::generateKey();
            $store->create($name, $key, $options);
            keysGuiFlashSet('success', "Created key <strong>" . keysGuiH($name) . "</strong>. Copy it now — it will not be shown again:<br><code class='user-select-all'>" . keysGuiH($key) . "</code>");
        }
        header('Location: api_keys_gui.php');
        exit;
    }

    if ($action === 'disable') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name !== '' && preg_match('/^[a-zA-Z0-9_\-]+$/', $name) && $store->exists($name)) {
            $store->disable($name);
            keysGuiFlashSet('info', 'Disabled key: ' . keysGuiH($name));
        } else {
            keysGuiFlashSet('danger', 'Key not found.');
        }
        header('Location: api_keys_gui.php');
        exit;
    }
}

if (!$authenticated) {
    $errorHtml = isset($loginError)
        ? '<div class="alert alert-danger">' . keysGuiH($loginError) . '</div>'
        : '';
    keysGuiRenderPage('API Keys — Login', '
        <h1>API Keys</h1>
        <p class="text-secondary">Sign in to manage API keys.</p>
        ' . $errorHtml . '
        <form method="post" class="card card-body" style="max-width: 24rem;">
            <input type="hidden" name="action" value="login">
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary">Sign in</button>
        </form>
        <p class="mt-3"><a href="api_gui.php">&larr; Back to endpoints</a></p>
    ');
    exit;
}

$store = ApiKeyStore::createFromConfig();
$keys = $store->listDetailed();
$flash = keysGuiFlashGet();
$csrf = keysGuiCsrfToken();

$flashHtml = '';
if ($flash !== null) {
    $flashHtml = '<div class="alert alert-' . keysGuiH($flash['type']) . '">' . $flash['message'] . '</div>';
}

$rowsHtml = '';
foreach ($keys as $key) {
    $allowed = $key['options']['allowedEndpoints'] ?? ['*'];
    $allowedStr = is_array($allowed) ? implode(', ', $allowed) : (string) $allowed;
    $statusBadge = $key['enabled']
        ? '<span class="badge bg-success">enabled</span>'
        : '<span class="badge bg-secondary">disabled</span>';
    $noTimeout = !empty($key['options']['noTimeOut']) ? 'yes' : 'no';
    $lastUsed = $key['last_used_at'] !== null ? keysGuiH($key['last_used_at']) : '<span class="text-secondary">never</span>';

    $disableBtn = '';
    if ($key['enabled']) {
        $disableBtn = '
            <form method="post" class="d-inline" onsubmit="return confirm(' . json_encode('Disable key ' . $key['name'] . '?') . ');">
                <input type="hidden" name="csrf" value="' . keysGuiH($csrf) . '">
                <input type="hidden" name="action" value="disable">
                <input type="hidden" name="name" value="' . keysGuiH($key['name']) . '">
                <button type="submit" class="btn btn-sm btn-outline-danger">Disable</button>
            </form>';
    }

    $rowsHtml .= '<tr>
        <td><strong>' . keysGuiH($key['name']) . '</strong></td>
        <td>' . $statusBadge . '</td>
        <td><code>' . keysGuiH($allowedStr) . '</code></td>
        <td>' . keysGuiH($noTimeout) . '</td>
        <td class="text-secondary">' . keysGuiH($key['created_at']) . '</td>
        <td class="text-secondary">' . $lastUsed . '</td>
        <td>' . $disableBtn . '</td>
    </tr>';
}

if ($rowsHtml === '') {
    $rowsHtml = '<tr><td colspan="7" class="text-secondary">No API keys yet.</td></tr>';
}

keysGuiRenderPage('API Keys', '
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">API Keys</h1>
        <div>
            <a href="api_gui.php" class="btn btn-outline-secondary btn-sm">Endpoints</a>
            <a href="api_keys_gui.php?logout=1" class="btn btn-outline-secondary btn-sm">Sign out</a>
        </div>
    </div>
    ' . $flashHtml . '
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title mb-0">Create key</h3></div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf" value="' . keysGuiH($csrf) . '">
                <input type="hidden" name="action" value="create">
                <div class="col-md-4">
                    <label class="form-label" for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name" pattern="[a-zA-Z0-9_\\-]+" required placeholder="my_key">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="endpoint">Restrict to endpoint <span class="text-secondary">(optional)</span></label>
                    <input type="text" class="form-control" id="endpoint" name="endpoint" placeholder="* = unrestricted if empty">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input" name="no_timeout" id="no_timeout">
                        <span class="form-check-label">No timeout</span>
                    </label>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Create</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">All keys</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Allowed endpoints</th>
                        <th>No timeout</th>
                        <th>Created</th>
                        <th>Last used</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>' . $rowsHtml . '</tbody>
            </table>
        </div>
    </div>
');
