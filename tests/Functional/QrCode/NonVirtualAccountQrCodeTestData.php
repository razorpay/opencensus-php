<?php

use RZP\Gateway\Upi\Icici\Fields;

return [
    'testCreateBharatQrCode' => [
        'name'           => 'Test QR Code',
        'description'    => 'QR code for tests',
        'usage'          => 'multiple_use',
        'type'           => 'bharat_qr',
        'fixed_amount'   => false,
        'notes'          => [
            'a' => 'b',
        ],
    ],

    'testCreateUpiQrCode' => [
        'name'           => 'Test QR Code',
        'description'    => 'QR code for tests',
        'usage'          => 'multiple_use',
        'type'           => 'upi_qr',
        'fixed_amount'   => false,
        'notes'          => [
            'a' => 'b',
        ],
    ],

    'testProcessIciciQrPayment' => [
        'url'     => '/payment/callback/bharatqr/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => 'abcd_bharat_qr',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'Havdshc12Dacftqrv2',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],
];
