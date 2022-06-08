<?php

return [
    'midtrans' => [
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'server_key' => env('MIDTRANS_SERVER_KEY', ''),
        'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
        'midtrans_url' => env('MIDTRANS_URL', 'https://api.sandbox.midtrans.com/v2/'),
        'auth_string' => env('MIDTRANS_AUTH_STRING', ''),
    ],
    'xendit' => [
        'is_production' => env('XENDIT_IS_PRODUCTION', false),
        'public_key' => env('XENDIT_API_KEY', ''),
        'secret_key' => env('XENDIT_SECRET_KEY', ''),
        'auth_string' => env('XENDIT_AUTH_STRING', ''),
    ],
];
