<!DOCTYPE html>

<?php
require_once __DIR__ . '/api_settings.php';
require_once __DIR__ . '/api_base.php';
require_once __DIR__ . '/api_endpoints.php';
require_once __DIR__ . '/lib/endpoint_discovery.php';

if (!defined('ENABLE_API_GUI') || ENABLE_API_GUI !== true) {
    http_response_code(404);
    echo 'Not found.';
    exit;
}

function guiH(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API GUI</title>
    <link rel="stylesheet" href="https://ubuntu.roste.org/_assets/tabler.min.css">
    <script src="https://ubuntu.roste.org/_assets/tabler.min.js"></script>
    <style>
        .gui-shell { max-width: 1600px; }
        .endpoint-card { margin-bottom: 1rem; }
        .try-result-shell { display: flex; flex-direction: column; min-height: 14rem; }
        .try-result-shell .alert { flex: 1; display: flex; align-items: center; }
        .try-result {
            max-height: 42rem;
            min-height: 14rem;
            overflow: auto;
            font-size: 0.8125rem;
            line-height: 1.45;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            white-space: pre;
            tab-size: 2;
            margin-bottom: 0;
            background-color: #151922;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .try-result code { color: #cbd5e1; }
        .json-key { color: #7dd3fc; }
        .json-string { color: #86efac; }
        .json-number { color: #fcd34d; }
        .json-boolean { color: #f9a8d4; }
        .json-null { color: #9ca3af; }
        .badge-open { background-color: #2fb344; color: #fff !important; }
        .badge-protected { background-color: #626976; color: #fff !important; }
    </style>
</head>
<body data-bs-theme="dark">
    <div class="container-fluid gui-shell pt-5 pb-5 px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">API Endpoints</h1>
            <div>
                <?php if (defined('ENABLE_API_KEYS_GUI') && ENABLE_API_KEYS_GUI === true && defined('KEY_STORE_DRIVER') && KEY_STORE_DRIVER !== 'php'): ?>
                    <a href="api_keys_gui.php" class="btn btn-outline-primary btn-sm">Manage API Keys</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <label class="form-label" for="endpoint-search">Search endpoints</label>
                <input type="search" class="form-control" id="endpoint-search" placeholder="Filter by name or file...">
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-5 col-lg-6" id="endpoint-list">
                <?php foreach (discoverEndpointPhpFiles() as $endpointFile): ?>
                    <?php $functions = discoverApiFunctionsReflection($endpointFile); ?>
                    <?php if ($functions === []) continue; ?>
                    <?php
                    $section = str_starts_with($endpointFile, __DIR__ . '/endpoints/examples/')
                        ? 'examples/' . basename($endpointFile, '.php')
                        : basename($endpointFile, '.php');
                    ?>
                    <div class="card endpoint-card" data-section="<?= guiH($section) ?>">
                        <div class="card-header"><h3 class="card-title mb-0"><?= guiH($section) ?></h3></div>
                        <div class="card-body">
                            <?php foreach ($functions as $func): ?>
                                <?php $isOpen = endpointIsOpen($func['clean']); ?>
                                <div class="mb-3 endpoint-item" data-name="<?= guiH($func['clean']) ?>">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <strong class="text-warning"><?= guiH($func['clean']) ?></strong>
                                        <span class="badge <?= $isOpen ? 'badge-open' : 'badge-protected' ?>"><?= $isOpen ? 'open' : 'auth' ?></span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto try-btn"
                                            data-endpoint="<?= guiH($func['clean']) ?>"
                                            data-open="<?= $isOpen ? '1' : '0' ?>"
                                            data-params="<?= guiH(json_encode($func['parameters'], JSON_THROW_ON_ERROR)) ?>">
                                            Try it
                                        </button>
                                    </div>
                                    <?php if ($func['params'] !== ''): ?>
                                        <code class="text-secondary small"><?= guiH($func['params']) ?></code>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="col-xl-7 col-lg-6">
                <div class="card sticky-top try-panel" style="top: 1rem;">
                    <div class="card-header"><h3 class="card-title mb-0">Try endpoint</h3></div>
                    <div class="card-body">
                        <form id="try-form">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="try-endpoint">Endpoint</label>
                                    <input type="text" class="form-control" id="try-endpoint" name="endpoint" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="try-apikey">API key <span class="text-secondary">(if required)</span></label>
                                    <input type="password" class="form-control" id="try-apikey" name="apikey" autocomplete="off">
                                </div>
                            </div>
                            <div class="mb-3 mt-3" id="try-params"></div>
                            <button type="submit" class="btn btn-primary">Send request</button>
                        </form>
                        <div class="try-result-shell mt-3 flex-grow-1">
                            <div id="try-result-alert" class="alert d-none mb-0" role="status"></div>
                            <pre class="try-result p-3 rounded mb-0" id="try-result"><code id="try-result-code">Response will appear here.</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('endpoint-search');
        const tryForm = document.getElementById('try-form');
        const tryEndpoint = document.getElementById('try-endpoint');
        const tryApikey = document.getElementById('try-apikey');
        const tryParams = document.getElementById('try-params');
        const tryResult = document.getElementById('try-result');
        const tryResultCode = document.getElementById('try-result-code');
        const tryResultAlert = document.getElementById('try-result-alert');

        function showTryPre() {
            tryResultAlert.classList.add('d-none');
            tryResult.classList.remove('d-none');
        }

        function showTryAlert(message, variant) {
            tryResult.classList.add('d-none');
            tryResultAlert.className = 'alert alert-' + variant + ' mb-0';
            tryResultAlert.textContent = message;
            tryResultAlert.classList.remove('d-none');
        }

        function setTryResultPlain(text, tone) {
            showTryPre();
            tryResultCode.textContent = text;
            tryResultCode.className = '';
            if (tone === 'ready') {
                showTryAlert(text, 'success');
            } else if (tone === 'loading') {
                showTryAlert(text, 'info');
            }
        }

        function setTryResultJson(value) {
            showTryPre();
            tryResultCode.className = 'language-json';
            tryResultCode.innerHTML = highlightJson(value);
        }

        function highlightJson(value) {
            const json = JSON.stringify(value, null, 2);
            const escaped = json
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            return escaped.replace(
                /("(\\u[\da-fA-F]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g,
                function (match) {
                    let cls = 'json-number';
                    if (/^"/.test(match)) {
                        cls = /:$/.test(match) ? 'json-key' : 'json-string';
                    } else if (/true|false/.test(match)) {
                        cls = 'json-boolean';
                    } else if (/null/.test(match)) {
                        cls = 'json-null';
                    }
                    return '<span class="' + cls + '">' + match + '</span>';
                }
            );
        }

        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.endpoint-card').forEach(function (card) {
                const section = (card.dataset.section || '').toLowerCase();
                let any = false;
                card.querySelectorAll('.endpoint-item').forEach(function (item) {
                    const name = (item.dataset.name || '').toLowerCase();
                    const show = !q || section.includes(q) || name.includes(q);
                    item.style.display = show ? '' : 'none';
                    if (show) any = true;
                });
                card.style.display = any ? '' : 'none';
            });
        });

        function renderParams(parameters) {
            tryParams.innerHTML = '';
            parameters.forEach(function (param) {
                const wrap = document.createElement('div');
                wrap.className = 'mb-2';
                const label = document.createElement('label');
                label.className = 'form-label';
                label.textContent = param.name + (param.optional ? ' (optional)' : '');
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.name = 'param_' + param.name;
                input.dataset.paramName = param.name;
                if (param.optional && param.default !== null && param.default !== undefined) {
                    input.placeholder = 'default: ' + String(param.default);
                }
                wrap.appendChild(label);
                wrap.appendChild(input);
                tryParams.appendChild(wrap);
            });
        }

        document.querySelectorAll('.try-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                tryEndpoint.value = btn.dataset.endpoint;
                tryApikey.required = btn.dataset.open !== '1';
                renderParams(JSON.parse(btn.dataset.params || '[]'));
                setTryResultPlain('Ready to send request.', 'ready');
            });
        });

        tryForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const params = new URLSearchParams();
            params.set('endpoint', tryEndpoint.value);
            if (tryApikey.value) {
                params.set('apikey', tryApikey.value);
            }
            tryParams.querySelectorAll('input[data-param-name]').forEach(function (input) {
                if (input.value !== '') {
                    params.set(input.dataset.paramName, input.value);
                }
            });
            setTryResultPlain('Loading...', 'loading');
            try {
                const response = await fetch('index.php?' + params.toString());
                const text = await response.text();
                try {
                    setTryResultJson(JSON.parse(text));
                } catch (e) {
                    setTryResultPlain(text);
                }
            } catch (err) {
                setTryResultPlain(String(err));
            }
        });
    </script>
</body>
</html>
