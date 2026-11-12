<?php
return [
    'admin_email' => env('ADMIN_EMAIL', ''),
    'admin_password' => env('ADMIN_PASSWORD', ''),
    'midtrans_server_key' => env('MIDTRANS_SERVER_KEY', ''),
    'midtrans_client_key' => env('MIDTRANS_CLIENT_KEY', ''),
    'midtrans_production' => filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN),
];
