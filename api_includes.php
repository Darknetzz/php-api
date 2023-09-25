<?php
$includes = [
    'api_settings',
    'api_base',
    'api_endpoints',
    'api_keys',
    'api_aliases',
];

foreach ($includes as $include) {
    # Custom
    $custom = 'custom_'.$include.'.php';

    # Default
    $default = $include.'.php';

    if (file_exists($custom)) {
        require_once($custom);
    } elseif (file_exists($default)) {
        require_once($default);
    } else {
        die("Failed to include $include<br>");
    }
}
?>