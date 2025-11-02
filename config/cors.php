<?php

return [
    // Apply CORS primarily to API endpoints used by the frontend
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    // Allow all HTTP methods for development
    'allowed_methods' => ['*'],

    // Explicitly allow common dev origins (Vite dev server)
    // You can override/extend via env using VITE_DEV_ORIGIN
    'allowed_origins' => array_values(array_filter([
        env('VITE_DEV_ORIGIN'),               // e.g. http://192.168.0.197:5173
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://192.168.0.197:5173',
    ])),

    'allowed_origins_patterns' => [],

    // Accept any headers; Laravel will still enforce CSRF/session as applicable
    'allowed_headers' => ['*'],

    'exposed_headers' => [],
    'max_age' => 0,

    // We use session auth; credentials must be supported
    'supports_credentials' => true,
];
