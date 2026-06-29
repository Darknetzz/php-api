<!DOCTYPE html>

<?php
require_once __DIR__ . '/api_settings.php';
require_once __DIR__ . '/lib/endpoint_discovery.php';
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API GUI</title>
    
    <!-- tabler.io -->
    <link rel="stylesheet" href="https://ubuntu.roste.org/_assets/tabler.min.css">
    <script src="https://ubuntu.roste.org/_assets/tabler.min.js"></script>

</head>

<body data-bs-theme="dark">

    <div class="container pt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0">API Endpoints</h1>
            <?php if (defined('ENABLE_API_KEYS_GUI') && ENABLE_API_KEYS_GUI === true && defined('KEY_STORE_DRIVER') && KEY_STORE_DRIVER !== 'php'): ?>
                <a href="api_keys_gui.php" class="btn btn-outline-primary btn-sm">Manage API Keys</a>
            <?php endif; ?>
        </div>
        <p>List of available API endpoints:</p>
        <ul>
            <?php
            foreach (discoverEndpointPhpFiles() as $endpoint) {
                $functions = discoverApiFunctionsFromFile($endpoint);
                if ($functions === []) {
                    continue;
                }
                $endpoint_name = basename($endpoint, '.php');
                $section = str_starts_with($endpoint, __DIR__ . '/endpoints/examples/')
                    ? 'examples/' . $endpoint_name
                    : $endpoint_name;
                echo '<h3>' . htmlspecialchars($section, ENT_QUOTES, 'UTF-8') . '</h3>';
                foreach ($functions as $func) {
                    $func_name_clean = htmlspecialchars($func['clean'], ENT_QUOTES, 'UTF-8');
                    echo '<ul><li><strong class="text-warning">Function:</strong> <a href="index.php?endpoint=' . $func_name_clean . '">' . $func_name_clean . '</a>';
                    if ($func['params'] !== '') {
                        $param_list = array_map('trim', explode(',', $func['params']));
                        echo '<ul>';
                        foreach ($param_list as $param) {
                            echo '<li><em class="text-secondary">Param:</em> ' . htmlspecialchars($param, ENT_QUOTES, 'UTF-8') . '</li>';
                        }
                        echo '</ul>';
                    }
                    echo '</li></ul>';
                }
                echo '<hr>';
            }
            ?>
        </ul>
    </div>

</body>