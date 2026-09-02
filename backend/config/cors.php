<?php

$origins = array_filter(array_map(
    'trim',
    explode(',', env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://127.0.0.1:5173') . ',http://localhost:5173'))
));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $origins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
