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

/** @return list<string> */
function keysGuiDiscoverEndpoints(): array
{
    require_once __DIR__ . '/lib/endpoint_discovery.php';

    $endpoints = [];
    foreach (discoverEndpointPhpFiles() as $file) {
        foreach (discoverApiFunctionsFromFile($file) as $func) {
            $endpoints[$func['clean']] = true;
        }
    }

    $list = array_keys($endpoints);
    sort($list, SORT_NATURAL | SORT_FLAG_CASE);

    return $list;
}

/** @param list<string> $available @param list<string> $selected */
function keysGuiMergeEndpointLists(array $available, array $selected): array
{
    $extra = array_values(array_filter($selected, static fn(string $ep): bool => $ep !== '*'));
    $merged = array_values(array_unique(array_merge($available, $extra)));
    sort($merged, SORT_NATURAL | SORT_FLAG_CASE);

    return $merged;
}

/** @return list<string> */
function keysGuiParseEndpointsFromRequest(string $prefix): array
{
    $allKey = $prefix . '_all_endpoints';
    $listKey = $prefix . '_endpoints';

    if (!empty($_POST[$allKey])) {
        return ['*'];
    }

    $selected = $_POST[$listKey] ?? [];
    if (!is_array($selected)) {
        $selected = [$selected];
    }

    $endpoints = [];
    foreach ($selected as $ep) {
        $ep = trim((string) $ep);
        if ($ep === '' || $ep === '*') {
            continue;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $ep)) {
            return [];
        }
        $endpoints[] = $ep;
    }

    return $endpoints === [] ? ['*'] : array_values(array_unique($endpoints));
}

/** @param list<string> $available @param list<string> $selected */
function keysGuiRenderEndpointsField(string $prefix, array $available, array $selected, bool $allEndpoints): string
{
    $selectId = $prefix . '_endpoints';
    $allName = $prefix . '_all_endpoints';
    $listName = $prefix . '_endpoints[]';
    $selectedSet = array_flip($selected);
    $optionsHtml = '';

    foreach ($available as $ep) {
        $isSelected = !$allEndpoints && isset($selectedSet[$ep]);
        $optionsHtml .= '<option value="' . keysGuiH($ep) . '"' . ($isSelected ? ' selected' : '') . '>' . keysGuiH($ep) . '</option>';
    }

    $selectDisabled = $allEndpoints ? ' disabled' : '';

    return '
        <label class="form-check mb-2">
            <input type="checkbox" class="form-check-input keys-all-endpoints" data-target="' . keysGuiH($selectId) . '" name="' . keysGuiH($allName) . '" value="1"' . ($allEndpoints ? ' checked' : '') . '>
            <span class="form-check-label">All endpoints (<code>*</code>)</span>
        </label>
        <select class="form-select keys-endpoints-select" id="' . keysGuiH($selectId) . '" name="' . keysGuiH($listName) . '" multiple' . $selectDisabled . '>
            ' . $optionsHtml . '
        </select>
        <div class="form-text text-secondary">Pick endpoints from the dropdown. Empty selection defaults to all endpoints.</div>';
}

function keysGuiValidName(string $name): bool
{
    return $name !== '' && strlen($name) <= 64 && (bool) preg_match('/^[\p{L}\p{N}_\- ]+$/u', $name);
}

function keysGuiRenderPage(string $title, string $body, bool $openEditModal = false, string $modal = ''): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= keysGuiH($title) ?></title>
    <link rel="stylesheet" href="https://ubuntu.roste.org/_assets/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/css/tom-select.bootstrap5.min.css">
    <script src="https://ubuntu.roste.org/_assets/tabler.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/js/tom-select.complete.min.js"></script>
    <style>
        .badge.keys-status-enabled { background-color: #2fb344; color: #fff !important; }
        .badge.keys-status-disabled { background-color: #626976; color: #fff !important; }
        .badge.keys-endpoints-all { background-color: #2fb344; color: #fff !important; font-weight: 600; }
        .keys-endpoints-select + .ts-wrapper { width: 100%; }
        .ts-wrapper .ts-control { min-height: 2.5rem; }
        [data-bs-theme="dark"] .ts-wrapper .ts-control,
        [data-bs-theme="dark"] .ts-wrapper .ts-dropdown {
            background-color: var(--tblr-bg-forms, #1f2937);
            border-color: var(--tblr-border-color, #374151);
            color: var(--tblr-body-color, #e5e7eb);
        }
        [data-bs-theme="dark"] .ts-wrapper .ts-dropdown .option.active {
            background-color: rgba(32, 107, 196, 0.25);
        }
    </style>
</head>
<body data-bs-theme="dark">
    <div class="container pt-5 pb-5">
        <?= $body ?>
    </div>
    <?= $modal ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var instances = {};
            document.querySelectorAll('.keys-endpoints-select').forEach(function (select) {
                instances[select.id] = new TomSelect(select, {
                    plugins: ['remove_button'],
                    maxItems: null,
                    placeholder: 'Select endpoints...',
                    dropdownParent: 'body',
                });
            });

            document.querySelectorAll('.keys-all-endpoints').forEach(function (checkbox) {
                var ts = instances[checkbox.dataset.target];
                if (!ts) return;
                var sync = function () {
                    if (checkbox.checked) {
                        ts.clear(true);
                        ts.disable();
                    } else {
                        ts.enable();
                    }
                };
                checkbox.addEventListener('change', sync);
                sync();
            });

            var modalEl = document.getElementById('editKeyModal');
            if (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', function () {
                    if (window.location.search.indexOf('edit=') !== -1) {
                        window.location.href = 'api_keys_gui.php';
                    }
                });
                <?php if ($openEditModal): ?>
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
                <?php endif; ?>
            }
        });
    </script>
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
        $allowedEndpoints = keysGuiParseEndpointsFromRequest('create');
        $noTimeout = isset($_POST['no_timeout']);

        if ($allowedEndpoints === []) {
            keysGuiFlashSet('danger', 'Invalid endpoint selection.');
        } elseif (!keysGuiValidName($name)) {
            keysGuiFlashSet('danger', 'Invalid key name. Use letters, numbers, underscores, and hyphens only.');
        } elseif ($store->exists($name)) {
            keysGuiFlashSet('danger', "Key name already exists: $name");
        } else {
            $options = ['allowedEndpoints' => $allowedEndpoints];
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

    if ($action === 'update') {
        $oldName = trim((string) ($_POST['old_name'] ?? ''));
        $newName = trim((string) ($_POST['name'] ?? ''));
        $allowedEndpoints = keysGuiParseEndpointsFromRequest('edit');

        if ($allowedEndpoints === []) {
            keysGuiFlashSet('danger', 'Invalid endpoint selection.');
            header('Location: api_keys_gui.php?edit=' . rawurlencode($oldName));
            exit;
        } elseif (!keysGuiValidName($oldName) || !$store->exists($oldName)) {
            keysGuiFlashSet('danger', 'Key not found.');
        } elseif (!keysGuiValidName($newName)) {
            keysGuiFlashSet('danger', 'Invalid key name. Use letters, numbers, underscores, and hyphens only.');
        } elseif ($newName !== $oldName && $store->exists($newName)) {
            keysGuiFlashSet('danger', "Key name already exists: $newName");
        } else {
            try {
                $store->updateKey($oldName, $newName, $allowedEndpoints);
                keysGuiFlashSet('success', 'Updated key: <strong>' . keysGuiH($newName) . '</strong>');
                header('Location: api_keys_gui.php');
                exit;
            } catch (InvalidArgumentException $e) {
                keysGuiFlashSet('danger', $e->getMessage());
            }
        }
        header('Location: api_keys_gui.php?edit=' . rawurlencode($oldName));
        exit;
    }

    if ($action === 'disable') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name !== '' && $store->exists($name)) {
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
$availableEndpoints = keysGuiDiscoverEndpoints();
$editName = trim((string) ($_GET['edit'] ?? ''));
$editKey = $editName !== '' ? $store->getByName($editName) : null;

$flashHtml = '';
if ($flash !== null) {
    $flashHtml = '<div class="alert alert-' . keysGuiH($flash['type']) . '">' . $flash['message'] . '</div>';
}

$editModalHtml = '';
$openEditModal = false;
if ($editKey !== null) {
    $allowed = $editKey['options']['allowedEndpoints'] ?? ['*'];
    if (!is_array($allowed)) {
        $allowed = ['*'];
    }
    $allEndpoints = in_array('*', $allowed, true);
    $endpointOptions = keysGuiMergeEndpointLists($availableEndpoints, $allowed);
    $editEndpointsField = keysGuiRenderEndpointsField('edit', $endpointOptions, $allowed, $allEndpoints);
    $openEditModal = true;
    $editModalHtml = '
    <div class="modal fade" id="editKeyModal" tabindex="-1" aria-labelledby="editKeyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editKeyModalLabel">Edit key</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf" value="' . keysGuiH($csrf) . '">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="old_name" value="' . keysGuiH($editKey['name']) . '">
                        <div class="mb-3">
                            <label class="form-label" for="edit_name">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required maxlength="64" value="' . keysGuiH($editKey['name']) . '">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Allowed endpoints</label>
                            ' . $editEndpointsField . '
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>';
}

$createEndpointsField = keysGuiRenderEndpointsField('create', $availableEndpoints, [], true);

$rowsHtml = '';
foreach ($keys as $key) {
    $allowed = $key['options']['allowedEndpoints'] ?? ['*'];
    $allowedStr = is_array($allowed) ? implode(', ', $allowed) : (string) $allowed;
    $isUnrestricted = is_array($allowed) && in_array('*', $allowed, true);
    $statusBadge = $key['enabled']
        ? '<span class="badge keys-status-enabled">enabled</span>'
        : '<span class="badge keys-status-disabled">disabled</span>';
    $allowedHtml = $isUnrestricted
        ? '<span class="badge keys-endpoints-all" title="All endpoints">*</span>'
        : '<code class="text-secondary">' . keysGuiH($allowedStr) . '</code>';
    $noTimeout = !empty($key['options']['noTimeOut']) ? 'yes' : 'no';
    $lastUsed = $key['last_used_at'] !== null ? keysGuiH($key['last_used_at']) : '<span class="text-secondary">never</span>';

    $disableBtn = '';
    $editBtn = '<a href="api_keys_gui.php?edit=' . rawurlencode($key['name']) . '" class="btn btn-sm btn-outline-primary">Edit</a>';
    if ($key['enabled']) {
        $disableBtn = '
            <form method="post" class="d-inline" onsubmit="return confirm(' . json_encode('Disable key ' . $key['name'] . '?') . ');">
                <input type="hidden" name="csrf" value="' . keysGuiH($csrf) . '">
                <input type="hidden" name="action" value="disable">
                <input type="hidden" name="name" value="' . keysGuiH($key['name']) . '">
                <button type="submit" class="btn btn-sm btn-outline-danger">Disable</button>
            </form>';
    }

    $rowsHtml .= '<tr' . ($editKey !== null && $editKey['name'] === $key['name'] ? ' class="table-active"' : '') . '>
        <td><strong>' . keysGuiH($key['name']) . '</strong></td>
        <td>' . $statusBadge . '</td>
        <td>' . $allowedHtml . '</td>
        <td>' . keysGuiH($noTimeout) . '</td>
        <td class="text-secondary">' . keysGuiH($key['created_at']) . '</td>
        <td class="text-secondary">' . $lastUsed . '</td>
        <td class="text-nowrap">' . $editBtn . ' ' . $disableBtn . '</td>
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
                <div class="col-md-3">
                    <label class="form-label" for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required placeholder="my_key" maxlength="64">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Allowed endpoints</label>
                    ' . $createEndpointsField . '
                </div>
                <div class="col-md-2 d-flex align-items-end pb-4">
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input" name="no_timeout" id="no_timeout">
                        <span class="form-check-label">No timeout</span>
                    </label>
                </div>
                <div class="col-md-2 d-flex align-items-end pb-4">
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
', $openEditModal, $editModalHtml);
