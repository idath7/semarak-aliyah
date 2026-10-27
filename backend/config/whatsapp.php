<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Gateway Configuration (OpenWA Cloud / Multi-Session)
    |--------------------------------------------------------------------------
    |
    | Konfigurasi default gateway WhatsApp untuk Semarak Aliyah XIV.
    | Nilai dapat ditimpa melalui environment (.env) atau melalui Menu Pengaturan Admin.
    |
    */

    'enabled' => env('WA_GATEWAY_ENABLED', true),

    'provider' => env('WA_GATEWAY_PROVIDER', 'openwa'),

    'gateway_url' => env('WA_GATEWAY_URL', 'https://wacld.maswahas.sch.id'),

    'api_key' => env('WA_API_KEY', 'owa_k1_42615b3b5088559ee636063f25e8ca7e801057e5a43091aae5ff55eb7a59dd68'),

    'sender_session' => env('WA_SENDER_SESSION', '0571fd01-e2d4-495e-9cf7-1c3c272b7b6b'),

    'endpoint_send' => env('WA_ENDPOINT_SEND', '/sendText'),

    'endpoint_status' => env('WA_ENDPOINT_STATUS', '/check-auth'),

    'auth_header_type' => env('WA_AUTH_HEADER_TYPE', 'api_key'),

    'chat_id_format' => env('WA_CHAT_ID_FORMAT', 'c_us'),

    'ssl_bypass' => env('WA_SSL_BYPASS', true),

    'admin_notification_numbers' => env('WA_ADMIN_NOTIFICATION_NUMBERS', '083850070767'),
];
