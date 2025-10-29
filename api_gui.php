<!DOCTYPE html>

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
        <h1>API Endpoints</h1>
        <p>List of available API endpoints:</p>
        <ul>
            <?php 
            $endpoints = glob("endpoints/*.php");
            foreach ($endpoints as $endpoint) {
                $endpoint_name = basename($endpoint, '.php');
                echo "<h3>".basename($endpoint_name)."</h3>";
                $functions = [];
                $contents = file_get_contents($endpoint);
                if (preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(([^)]*)\)/', $contents, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $func) {
                        $func_name       = $func[1];
                        $func_name_clean = preg_replace('/^api_/', '', $func_name);
                        $params          = trim($func[2]);
                        echo "<ul><li><strong class='text-warning'>Function:</strong> <a href='index.php?endpoint=$func_name_clean'>{$func_name_clean}</a>";
                        if ($params !== '') {
                            $param_list = array_map('trim', explode(',', $params));
                            echo "<ul>";
                            foreach ($param_list as $param) {
                                echo "<li><em class='text-secondary'>Param:</em> {$param}</li>";
                            }
                            echo "</ul>";
                        }
                        echo "</li></ul>";
                    }
                }
                echo "<hr>";
            }
            ?>
        </ul>
    </div>

</body>