<?php

namespace RZP\Tests\Functional\QrPayment;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Gateway\Upi\Icici\Fields;

return [
    'createVirtualAccount' => [
        'url'     => '/virtual_accounts',
        'method'  => 'post',
        'content' => [
            'receiver_types' => 'qr_code'
        ],
    ],

    'testQrPaymentProcess' => [
        'url'     => '/payment/callback/bharatqr/hitachi',
        'method'  => 'post',
        'content' => [
            'F002'       => '423156XXXXXX1234',
            'F003'       => '26000',
            'F004'       => '000000000200',
            'F011'       => 'abc123',
            'F012'       => '120000',
            'F013'       => '1212',
            'F037'       => 'somethingrandom',
            'F038'       => 'randomauthorization',
            'F039'       => '0',
            'F041'       => 'abc',
            'F042'       => 'random',
            'F043'       => 'RazorpayBangalore',
            'F102'       => 'paymentId',
            'PurchaseID' => 'tobefilled',
            'SenderName' => 'Random Name',
        ],
    ],

    'testMakeTestPayments' => [
        'url'     => '/payment/callback/bharatqr/sharp',
        'method'  => 'post',
    ],

    'testUpiQrPaymentProcess' => [
        'url'     => '/payment/callback/bharatqr/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::RESPONSE         => '92',
            Fields::MERCHANT_ID      => 'abcd_bharat_qr',
            Fields::SUBMERCHANT_ID   => '42324',
            Fields::TERMINAL_ID      => '2425',
            Fields::SUCCESS          => 'true',
            Fields::MESSAGE          => 'Transaction initiated',
            Fields::MERCHANT_TRAN_ID => 'tobefilled',
            Fields::BANK_RRN         => random_int(111111111, 999999999),
            Fields::PAYER_NAME       => 'Ria Garg',
            Fields::PAYER_VA         => 'random@icici',
            Fields::PAYER_AMOUNT     => '100.00',
        ],
    ],
];
