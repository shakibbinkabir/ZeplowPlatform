<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Public endpoints (sites/* and health) are called from the three Next.js
    | frontends at build time and the browser at runtime (contact form). Internal
    | endpoints are server-to-server and don't need CORS.
    |
    */

    'paths' => ['sites/*', 'health'],

    'allowed_methods' => ['GET', 'POST'],

    'allowed_origins' => [
        'https://zeplow.com',
        'https://www.zeplow.com',
        'https://narrative.zeplow.com',
        'https://logic.zeplow.com',
        'http://localhost:3000',
        'http://localhost:3001',
        'http://localhost:3002',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Content-Type', 'X-Build-Token'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,
];
