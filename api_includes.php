<?php
$includes = [
    'api_settings',
    'api_base',
    'api_endpoints',
    'api_keys',
    'api_aliases',
];

foreach ($includes as $include) {
    # Custom variant
    $custom = 'custom_'.$include.'.php';

    # Default
    $default = $include.'.php';

    if (file_exists($custom)) {
        define(strtoupper($include), $custom);
    } else {
        define(strtoupper($include), $default);
    }
}

require_once(API_SETTINGS);
require_once(API_BASE);
require_once(API_ENDPOINTS);
require_once(API_KEYS);
require_once(API_ALIASES);
?>