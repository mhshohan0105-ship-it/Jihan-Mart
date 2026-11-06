<?php

// Courier API credentials live in .env (saved from Admin → Shipping → Courier API), never in the database.
return [
    'steadfast' => [
        'base_url' => env('STEADFAST_BASE_URL', 'https://portal.packzy.com/api/v1'),
        'api_key' => env('STEADFAST_API_KEY'),
        'secret_key' => env('STEADFAST_SECRET_KEY'),
        // bearer token Steadfast must send with webhook calls (set the same value in the Steadfast panel)
        'webhook_secret' => env('STEADFAST_WEBHOOK_SECRET'),
        'tracking_url' => 'https://steadfast.com.bd/t/',
    ],
    'pathao' => [
        'base_url' => env('PATHAO_BASE_URL', 'https://api-hermes.pathao.com'),
        'client_id' => env('PATHAO_CLIENT_ID'),
        'client_secret' => env('PATHAO_CLIENT_SECRET'),
        'username' => env('PATHAO_USERNAME'),
        'password' => env('PATHAO_PASSWORD'),
        'store_id' => env('PATHAO_STORE_ID'),
    ],
];
