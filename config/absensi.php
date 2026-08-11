<?php

return [
    /*
    | Base URL aplikasi Absensi API (dipanggil server-to-server oleh Central).
    | Contoh: http://absensi.test (dev) / https://absensi.megakomsel.com (prod).
    */
    'base_url' => env('ABSENSI_BASE_URL', 'http://localhost:8000'),

    /*
    | Secret bersama untuk webhook provisioning
    | (header X-Absensi-Webhook-Secret). Wajib SAMA dengan
    | ABSENSI_WEBHOOK_SECRET di absensi-app.
    */
    'webhook_secret' => env('ABSENSI_WEBHOOK_SECRET', ''),

    /*
    | Secret bersama untuk signed token SSO. Wajib SAMA dengan
    | ABSENSI_SSO_SECRET di absensi-app.
    */
    'sso_secret' => env('ABSENSI_SSO_SECRET', ''),

    /*
    | Pola subdomain tenant: {slug}-absensi.megakomsel.com
    | Bisa menyertakan scheme + port (untuk dev: http://{slug}-absensi.test:3000).
    */
    'tenant_domain_pattern' => env('ABSENSI_TENANT_DOMAIN_PATTERN', 'https://{slug}-absensi.megakomsel.com'),
];
