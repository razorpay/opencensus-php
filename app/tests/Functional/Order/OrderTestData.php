<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;

return [
    'testCreateOrder' => [
        'request' => [
            'content' => [
                'amount'        => 500,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                // 'method'     => 'netbanking',
                // 'account_id' => '0040304030403040',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 500,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                // 'method'     => 'netbanking',
                // 'account_id' => '0040304030403040',
            ],
        ],
    ],

    'testGetOrder' => [
        'amount'        => 500,
        'currency'      => 'INR',
        'receipt'       => 'rcptid42',
    ],
];
