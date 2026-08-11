<?php

return [
    /*
    | Gateway aktif. Ubah via env: PAYMENT_GATEWAY=midtrans (saat siap).
    */
    'default' => env('PAYMENT_GATEWAY', 'manual_transfer'),

    /*
    | Rekening tujuan pembayaran manual transfer.
    */
    'bank' => [
        'bank_name' => env('PAYMENT_BANK_NAME', 'BCA'),
        'account_number' => env('PAYMENT_ACCOUNT_NUMBER', '1234567890'),
        'account_name' => env('PAYMENT_ACCOUNT_NAME', 'PT Urano Digital Nusantara'),
    ],

    /*
    | Daftar gateway yang bisa dipakai. Semua harus implement PaymentGatewayInterface.
    */
    'gateways' => [
        'manual_transfer' => [
            'class' => App\Core\Modules\Payment\Gateways\ManualTransferGateway::class,
        ],
        // 'midtrans' => [
        //     'class' => App\Core\Modules\Payment\Gateways\MidtransGateway::class,
        // ],
    ],
];
