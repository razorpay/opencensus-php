<?php

use  Carbon\Carbon;
use  RZP\Gateway\Isg\Field;

return [

    'createVirtualAccount' => [
        'url'     => '/virtual_accounts',
        'method'  => 'post',
        'content' => [
            'receiver_types' => 'qr_code'
        ],
    ],

    'testQrPaymentProcess' => [
        'url'     => '/payment/callback/bharatqr/isg',
        'method'  => 'post',
        'content' => [
            Field::PRIMARY_ID                   => 'tobeset',
            Field::SECONDARY_ID                 => 'reference_id',
            Field::MERCHANT_PAN                 => '4403844012084006',
            Field::TRANSACTION_ID               => '1817700802564',
            Field::TRANSACTION_DATE_TIME        =>  Carbon:: now()->format('Y-m-d H:i:s'),
            Field::TRANSACTION_AMOUNT           => '1.00',
            Field::AUTH_CODE                    => 'ab3456',
            Field::RRN                          =>  random_int(111111111111,999999999999),
            Field::CONSUMER_PAN                 => '4012001037141112',
            Field::STATUS_CODE                  => '00',
            Field::STATUS_DESC                  => 'Transaction Approved',
            Field::TIP_AMOUNT                   => '1.20',
        ],
    ],

    'testPartialRefund'  => [
        'url'     => '/payment/callback/bharatqr/isg',
        'method'  => 'post',
        'content' => [
            Field::PRIMARY_ID                   => 'tobeset',
            Field::SECONDARY_ID                 => 'reference_id',
            Field::MERCHANT_PAN                 => '4403844012084006',
            Field::TRANSACTION_ID               => '1817700802564',
            Field::TRANSACTION_DATE_TIME        =>  Carbon:: now()->format('Y-m-d H:i:s'),
            Field::TRANSACTION_AMOUNT           => '2.00',
            Field::AUTH_CODE                    => 'ab3456',
            Field::RRN                          =>  random_int(111111111111,999999999999),
            Field::CONSUMER_PAN                 => '4012001037141112',
            Field::STATUS_CODE                  => '00',
            Field::STATUS_DESC                  => 'Transaction Approved',
            Field::TIP_AMOUNT                   => '1.20',
        ],
    ],

];
