<?php

/* ────────────────────────────────────────────────────────────────────────── */
/*                                   api_endpoints.php                         */
/* ────────────────────────────────────────────────────────────────────────── */

require_once dirname(__FILE__) . '/lib/endpoint_discovery.php';

do {
    loadEndpointPhpFiles();
} while (false);
