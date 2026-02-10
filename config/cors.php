<?php

return [
    // Hier voegen we jouw superbuy route toe
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'superbuy/*'],

    'allowed_methods' => ['*'],

    // DIT IS BELANGRIJK: We geven Superbuy specifiek toegang
    'allowed_origins' => [
        'https://www.superbuy.com',
        'http://localhost:8000', // Of jouw lokale URL
        'http://127.0.0.1:8000'
    ],

    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false, // We gebruiken geen cookies meer, dus false is prima
];