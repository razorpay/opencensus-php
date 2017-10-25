<?php

namespace RZP\Tests\Functional\QrPayment;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'createVirtualAccount' => [
        'url'     => '/virtual_accounts',
        'method'  => 'post',
        'content' => [
            'receiver_types' => 'qr_code'
        ],
    ],

    'testQrPaymentProcess' => [
        'url'     => '/bharatqr/payment/process',
        'method'  => 'post',
        'content' => [
            'F002'       => '423156XXXXXX1234',
            'F003'       => '26000',
            'F004'       => '1.00',
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
            'SenderName' => 'Razorpay',
        ],
    ],
];
