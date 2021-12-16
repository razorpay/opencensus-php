<?php

use RZP\Gateway\Upi\Icici\Fields;

return [
    'createQrCodeConfigs' => [
        'url'     => '/payment/qr_codes/configs/create',
        'method'  => 'post',
        'content' => [
            'cut_off_time' => 1500,
        ],
    ],

    'testQrCodeConfigsFetch' => [
        'url'     => '/payment/qr_codes/configs',
        'method'  => 'get',
        'content' => [
        ],
    ],

    'testQrCodeConfigsUpdate' => [
        'url'     => '/payment/qr_codes/configs/update',
        'method'  => 'post',
        'content' => [
            'cut_off_time' => 1800,
        ],
    ],

    'testProcessIciciQrPayment' => [
        'url'     => '/payment/callback/bharatqr/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => '403343',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'Havdshc12Dacftqrv2',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '500.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],
];
