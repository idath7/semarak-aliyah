<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Portal & Multi-Domain Configuration
    |--------------------------------------------------------------------------
    |
    | Memisahkan Web Publik dan Portal Admin secara arsitektur:
    | - Di Lingkungan Lokal (Development):
    |     * Web Publik  : Port 8000 (http://localhost:8000)
    |     * Portal Admin: Port 8001 (http://localhost:8001)
    |
    | - Di Lingkungan Production (Domain / Subdomain):
    |     * Web Publik  : semarak.maswahas.sch.id (atau env('PUBLIC_DOMAIN'))
    |     * Portal Admin: admin-semarak.maswahas.sch.id (atau env('ADMIN_DOMAIN'))
    |
    */
    'public_port' => (int) env('PUBLIC_PORT', 8000),
    'admin_port'  => (int) env('ADMIN_PORT', 8001),

    'public_url'  => env('PUBLIC_URL', 'http://localhost:8000'),
    'admin_url'   => env('ADMIN_URL', 'http://localhost:8001'),

    'public_domain' => env('PUBLIC_DOMAIN', null),
    'admin_domain'  => env('ADMIN_DOMAIN', null),
];
