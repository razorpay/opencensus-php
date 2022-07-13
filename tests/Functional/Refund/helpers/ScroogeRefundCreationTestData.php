<?php

return [
    'callRefundsPaymentUpdate' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/refunds/payment_update',
            'content' => [
                'refunds' => [
                    [
                        'id'          => 'HEVJGxChqeBIXx',
                        'payment_id'  => 'HEVIv0dJDs3KsS',
                        'amount'      => '100',
                        'base_amount' => '100',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'callScroogeRefundTransactionCreate' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/refunds/transaction_create',
            'content' => [
                [
                    'id'               => 'HEVJGxChqeBIXx',
                    'payment_id'       => 'HEVIv0dJDs3KsS',
                    'amount'           => '100',
                    'base_amount'      => '100',
                    'gateway'          => 'hdfc',
                    'speed_decisioned' => 'normal',
                ],
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testRefundBackWriteOnApi' => [
        'request' => [
            'method'  => 'post',
            'url'     => '/scrooge/back_write_refund',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ],
    ],
];
